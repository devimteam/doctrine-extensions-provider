<?php

namespace Devim\Provider\DoctrineExtensionsServiceProvider\Command;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateDatabaseDoctrineCommand.
 */
class CreateDatabaseDoctrineCommand extends DoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('doctrine:database:create')
            ->setDescription('Creates the configured database')
            ->addOption('if-not-exists', null, InputOption::VALUE_NONE,
                'Don\'t trigger an error, when the database already exists')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command creates the default connections database:
    <info>php %command.full_name%</info>
EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ifNotExists = $input->getOption('if-not-exists');

        $connection = $this->getEntityManager()->getConnection();
        $params = $connection->getParams();

        $hasPath = isset($params['path']);
        $name = $hasPath ? $params['path'] : $params['dbname'] ?? false;

        if (!$name) {
            throw new \InvalidArgumentException('Connection does not contain a "path" or "dbname" parameter and cannot be dropped.');
        }

        unset($params['dbname'], $params['path'], $params['url']);

        $tmpConnection = DriverManager::getConnection($params);

        $shouldNotCreateDatabase = $ifNotExists && in_array($name, $tmpConnection->getSchemaManager()->listDatabases());

        if (!$hasPath) {
            $name = $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($name);
        }

        $error = false;
        try {
            if ($shouldNotCreateDatabase) {
                $output->writeln(sprintf('<info>Database <comment>%s</comment> already exists. Skipped.</info>',
                    $name));
            } else {
                $tmpConnection->getSchemaManager()->createDatabase($name);
                $output->writeln(sprintf('<info>Created database <comment>%s</comment></info>', $name));
            }
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Could not create database <comment>%s</comment></error>' . PHP_EOL,
                $name));
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            $error = true;
        }
        $tmpConnection->close();

        return $error ? 1 : 0;
    }
}
