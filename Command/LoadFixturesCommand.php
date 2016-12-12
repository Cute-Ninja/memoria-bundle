<?php

namespace CuteNinja\MemoriaBundle\Command;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Nelmio\Alice\Persister\Doctrine;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LoadFixturesCommand
 *
 * @package CuteNinja\MemoriaBundle\Command
 */
class LoadFixturesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cute_ninja:fixture:load')
            ->setDescription('Load data fixture for the current environment');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManagers = ['default'];

        if ($this->getContainer()->hasParameter('additional_entity_managers')) {
            $entityManagers = array_unique(array_merge($entityManagers, $this->getContainer()->getParameter('additional_entity_managers')));
        }

        $this->generateSchemas($entityManagers);

        $this->getContainer()->get('hautelook_alice.fixtures.loader')->load(new Doctrine($this->getEntityManager()), $this->getFixturesFiles());
    }

    /**
     * @param array $entityManagers
     */
    private function generateSchemas(array $entityManagers)
    {
        $defaultConnection = $this->getEntityManager()->getConnection();
        $metadata          = $this->getEntityManager()->getMetadataFactory()->getAllMetadata();

        foreach ($entityManagers as $entityManager) {
            $customConnection     = $this->getEntityManager($entityManager)->getConnection();
            $connectionParameters = $customConnection->getParams();

            $databaseName = isset($connectionParameters['dbname']) ? $connectionParameters['dbname'] : null;

            if (!$databaseName) {
                throw new \InvalidArgumentException("'dbname' parameter missing.");
            }

            try {
                $shouldCreateDatabase = !in_array($databaseName, $customConnection->getSchemaManager()->listDatabases());
            } catch (ConnectionException $e) {
                $shouldCreateDatabase = true;
            }

            if ($shouldCreateDatabase) {
                $defaultConnection->getSchemaManager()->dropAndCreateDatabase($databaseName);
            }

            $tool = new SchemaTool($this->getEntityManager($entityManager));
            $tool->dropSchema($metadata);
            $tool->createSchema($metadata);
        }
    }

    /**
     * @param string $managerName
     *
     * @return EntityManager
     */
    protected function getEntityManager($managerName = 'default')
    {
        return $this->getContainer()->get('doctrine')->getManager($managerName);
    }

    /**
     * @return array
     */
    private function getFixturesFiles()
    {
        $files    = [];
        $fixtures = $this->getContainer()->getParameter('fixtures');

        foreach ($fixtures as $fixture) {
            $files[] = $fixture['resource'];
        }

        return $files;
    }
}
