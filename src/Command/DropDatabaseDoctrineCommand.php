<?php

namespace Devimteam\Provider\DoctrineExtensionsServiceProvider\Command;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DropDatabaseDoctrineCommand.
 */
class DropDatabaseDoctrineCommand extends DoctrineCommand
{
    const RETURN_CODE_NOT_DROP = 1;
    const RETURN_CODE_NO_FORCE = 2;

    protected function configure()
    {
        $this
            ->setName('doctrine:database:drop')
            ->setDescription('Drops the configured database')
            ->addOption('if-exists', null, InputOption::VALUE_NONE,
                'Don\'t trigger an error, when the database doesn\'t exist')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command drops the default connections database:
    <info>php %command.full_name%</info>
The <info>--force</info> parameter has to be used to actually drop the database.
<error>Be careful: All data in a given database will be lost when executing this command.</error>
EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ifExists = $input->getOption('if-exists');

        $connection = $this->getEntityManager()->getConnection();
        $params = $connection->getParams();

        $hasPath = isset($params['path']);
        $name = $hasPath ? $params['path'] : $params['dbname'] ?? false;

        if (!$name) {
            throw new \InvalidArgumentException('Connection does not contain a "path" or "dbname" parameter and cannot be dropped.');
        }

        unset($params['dbname'], $params['path'], $params['url']);

        if ($input->getOption('force')) {
            $connection->close();
            $connection = DriverManager::getConnection($params);
            $shouldDropDatabase = !$ifExists || in_array($name, $connection->getSchemaManager()->listDatabases());

            if (!isset($params['path'])) {
                $name = $connection->getDatabasePlatform()->quoteSingleIdentifier($name);
            }

            try {
                if ($shouldDropDatabase) {
                    $connection->getSchemaManager()->dropDatabase($name);
                    $output->writeln(sprintf('<info>Dropped database for connection named <comment>%s</comment></info>',
                        $name));
                } else {
                    $output->writeln(sprintf('<info>Database for connection named <comment>%s</comment> doesn\'t exist. Skipped.</info>',
                        $name));
                }
            } catch (\Exception $e) {
                $output->writeln(sprintf('<error>Could not drop database for connection named <comment>%s</comment></error>' . PHP_EOL,
                    $name));
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

                return self::RETURN_CODE_NOT_DROP;
            }
        } else {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.' . PHP_EOL);
            $output->writeln(sprintf('<info>Would drop the database named <comment>%s</comment>.</info>', $name));
            $output->writeln('Please run the operation with --force to execute');
            $output->writeln('<error>All data will be lost!</error>');

            return self::RETURN_CODE_NO_FORCE;
        }
    }
}
