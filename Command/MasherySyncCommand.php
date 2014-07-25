<?php
/**
 * @author AlexanderC <self@alexanderc.me>
 * @date 4/15/14
 * @time 7:51 PM
 */

namespace AlexanderC\Api\MasheryBundle\Command;


use AlexanderC\Api\Mashery\Helpers\ObjectSyncer;
use AlexanderC\Api\Mashery\MsrQL;
use AlexanderC\Api\Mashery\QueryResult;
use AlexanderC\Api\Mashery\QueryResponse;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AlexanderC\Api\Mashery\Response;
use Symfony\Component\Yaml\Yaml;

class MasherySyncCommand extends ContainerAwareCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('hrm-data:mashery:sync-schema')
            ->setDescription('Sync Mashery to local schema')
            ->addArgument(
                'schema',
                InputArgument::OPTIONAL,
                "Sync specific schema instead of using all schemas found",
                null
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schema = $input->getArgument("schema");
        $schemas = [];

        if(null !== $schema) {
            $schemaFile = $this->getSchemaPath($schema);

            if(!file_exists($schemaFile) || is_readable($schemaFile)) {
                throw new \RuntimeException("Schema file does not exists or is not readable");
            }

            $schemas[] = $schemaFile;
        }

        $output->writeln("<info>Find available schemas...</info>");
        $schemas = empty($schemas) ? $this->findSchemas() : $schemas;
        $output->writeln("<info>" . count($schemas) . " available schemas found.</info>");

        $output->writeln("<info>Parsing found schemas.</info>");
        $schemas = array_map(function($schemaFile) { return Yaml::parse($schemaFile); }, $schemas);

        $output->writeln("<info>Validating parsed schemas.</info>");
        array_map([$this, 'validateSchema'], $schemas);

        foreach($schemas as $schema) {
            $output->writeln("<info>Syncing schema for entity -> {$schema['entity']}</info>");

            $this->syncSchema($schema, $output);
        }
    }

    /**
     * @param array $schema
     * @param OutputInterface $output
     */
    protected function syncSchema(array $schema, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $entityClass = $schema['entity'];
        $repositoryIdentifier = $schema['repository'];
        $repository = $em->getRepository($repositoryIdentifier);
        $identifierProperty = $schema['identifier'];

        $queryResponse = $this->getMashery()->query($schema['sync_query']);

        if($queryResponse->isError()) {
            $output->writeln("<error>Error while syncing {$repositoryIdentifier}".
                " schema: {$queryResponse->getError()->getMessage()}</error>");
            return;
        }

        $queryResult = $queryResponse->getResult();

        if($queryResult->getTotalItems() <= 0) {
            $output->writeln("<info>Nothing to sync for {$repositoryIdentifier}!</info>");
            return;
        }

        $output->writeln("<info>Start syncing {$queryResult->getTotalItems()} items...</info>");

        foreach($queryResult->getItems() as $item) {
            $identifier = $item[$identifierProperty];

            $output->writeln("<info>Start syncing item #{$identifier}</info>");

            $object = $repository->findOneBy(['mashery_object_id' => $identifier]);

            // if nothing with such mashery id found...
            if(null === $object) {
                $output->writeln("<info>No object with identifier #{$identifier} found! Creating it...</info>");

                $object = new $entityClass;

                // inject mashery identifier into the object
                ObjectSyncer::sync($object, [$identifierProperty => $identifier]);

                // hook to deny new package creation
                $object->setMasherySyncState(false);
            } else {
                $output->writeln("<info>Object with identifier #{$identifier} already exists</info>");
            }

            // fetch new data and update the object
            $this->getMashery()->fetch($object);

            $output->writeln("<info>Updating object #{$identifier}</info>");

            // save it locally
            $em->persist($object);
            $em->flush();

            // allow object syncing
            $object->setMasherySyncState(true);
        }

        $entities = $repository->findAll();

        // sync relations
        if(isset($schema['links']) && is_array($schema['links']) && !empty($schema['links'])) {
            $links = $schema['links'];

            $output->writeln("<info>Updating {$repositoryIdentifier} relations...</info>");

            foreach($links as $linkInfo) {
                list($linkEntityRepository,
                    $linkEntityTable, $linkingEntityTable,
                    $linkedEntitySetter, $linkingEntitySetter) = $this->parseLink($linkInfo);

                foreach($entities as $entity) {
                    $query = MsrQL::create()
                        ->select("id")
                        ->from($linkEntityTable)
                        ->requireRelated($linkingEntityTable, sprintf("id = %d", $entity->getMasheryObjectId()))
                    ;

                    $queryResponse = $this->getMashery()->query($query);

                    if($queryResponse->isError()) {
                        $output->writeln("<error>Error while linking {$repositoryIdentifier} ".
                            "to {$linkEntityRepository}: {$queryResponse->getError()->getMessage()}</error>");
                        continue;
                    }

                    $queryResult = $queryResponse->getResult();

                    if($queryResult->getTotalItems() <= 0) {
                        $output->writeln(
                            "<info>Nothing to link found between {$repositoryIdentifier} and {$linkEntityRepository}!</info>"
                        );
                        continue;
                    }

                    foreach($queryResult->getItems() as $linkInfo) {
                        $linkEntityId = $linkInfo['id'];

                        $linkEntity = $em->getRepository($linkEntityRepository)->findOneBy([
                            'mashery_object_id' => $linkEntityId
                        ]);

                        if(null === $linkEntity) {
                            $output->writeln(
                                "<error>No entity found for {$linkEntityRepository} #{$linkEntityId}</error>"
                            );
                            continue;
                        }

                        $output->writeln("<info>Linking {$linkEntityRepository} #{$linkEntityId}".
                            " with {$repositoryIdentifier}!</info>");

                        call_user_func([$entity, $linkingEntitySetter], $linkEntity);
                        call_user_func([$linkEntity, $linkedEntitySetter], $entity);

                        $em->persist($entity);
                        $em->persist($linkEntity);
                        $em->flush();
                    }
                }
            }
        }
    }

    /**
     * @param string $linkInfo
     * @return array
     * @throws \RuntimeException
     */
    protected function & parseLink($linkInfo)
    {
        $regex = '#^\s*link\s+(\S+)\s+as\s+(\w+)\s+using\s+(\w+)\s+updated\s+by\s+(\w+)\s+reversed\s+by\s+(\w+)\s*$#ui';

        if(!preg_match($regex, $linkInfo, $matches)) {
            throw new \RuntimeException("Invalid link exception: {$linkInfo}");
        }

        array_shift($matches);

        return $matches;
    }

    /**
     * @param array $schema
     * @throws \RuntimeException
     */
    protected function validateSchema(array $schema)
    {
        $mandatoryProperties = [
            'entity', 'repository',
            'sync_query', 'identifier',
        ];

        foreach($mandatoryProperties as $property) {
            if(!isset($schema[$property])) {
                throw new \RuntimeException("No property {$property} for schema: " . implode(", ", $schema));
            }
        }
    }

    /**
     * @return array
     */
    protected function findSchemas()
    {
        $rootPath = $this->getSyncSchemasRoot();

        return glob("{$rootPath}*.yml");
    }

    /**
     * @param string $schema
     * @return string
     */
    protected function getSchemaPath($schema)
    {
        $rootPath = $this->getSyncSchemasRoot();

        return $rootPath . $schema . '.yml';
    }

    /**
     * @return string
     */
    protected function getSyncSchemasRoot()
    {
        static $path = null;

        if(null === $path) {
            $path = rtrim($this->getContainer()->getParameter('mashery_api_sync_schemas'), "/") . "/";
        }

        return $path;
    }

    /**
     * @return \AlexanderC\Api\Mashery\Mashery
     */
    protected function getMashery()
    {
        return $this->getContainer()->get('mashery.api');
    }
}