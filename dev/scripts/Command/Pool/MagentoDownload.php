<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Pool;

use MagentoDevBox\Command\AbstractCommand;
use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use MagentoDevBox\Command\Options\MagentoCloud as MagentoCloudOptions;
use MagentoDevBox\Command\Options\Composer as ComposerOptions;
use MagentoDevBox\Library\Registry;
use MagentoDevBox\Library\XDebugSwitcher;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for downloading Magento sources
 */
class MagentoDownload extends AbstractCommand
{
    /**
     * @var int
     */
    private $keysAvailabilityInterval = 40;

    /**
     * @var int
     */
    private $maxAttemptsCount = 10;

    /**
     * @var bool
     */
    private $sshKeyIsNew = false;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:download')
            ->setDescription('Download Magento sources')
            ->setHelp('This command allows you to download Magento sources.');

        parent::configure();
    }

    /**
     * Define whether dir is empty
     *
     * @param $dir
     * @return bool
     */
    private function isEmptyDir($dir)
    {
        return !count(glob("/$dir/*"));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $enableSyncMarker = $input->getOption(MagentoOptions::ENABLE_SYNC_MARKER);

        if ($enableSyncMarker) {
            $statePath = $input->getOption(MagentoOptions::STATE_PATH);
            $syncMarkerPath =  $statePath . '/' . $enableSyncMarker;

            if (file_exists($syncMarkerPath)) {
                $this->executeCommands(sprintf('unlink %s', $syncMarkerPath), $output);
            }
        }

        $magentoPath = $input->getOption(MagentoOptions::PATH);
        $authFile = '/home/magento2/.composer/auth.json';
        $rootAuth = sprintf('%s/auth.json', $magentoPath);
        $customAuth = '';

        if (!file_exists($authFile) && !file_exists($rootAuth)) {
            $auth = $this->generateAuthConfig($input, $output);
            file_put_contents($authFile, $auth);
        } else {
            $auth = $this->generateAuthConfig($input, $output, true);
            if ($auth) {
                $customAuth = sprintf(' COMPOSER_AUTH="%s" ', addslashes($auth));
            }
        }

        $useExistingSources = $this->requestOption(MagentoOptions::SOURCES_REUSE, $input, $output)
            || !$this->isEmptyDir($magentoPath);
        $installFromCloud = $input->getOption(MagentoCloudOptions::SILENT_INSTALL)
            || $this->requestOption(MagentoCloudOptions::INSTALL, $input, $output);

        if ($useExistingSources) {
            XDebugSwitcher::switchOff();
            $composerJsonExists = file_exists(sprintf('%s/composer.json', $magentoPath));
            if ($composerJsonExists) {
                $this->executeCommands(sprintf('cd %s && %s composer install', $magentoPath, $customAuth), $output);
            }
            XDebugSwitcher::switchOn();
        } else if ($installFromCloud) {
            XDebugSwitcher::switchOff();
            $this->installFromCloud(
                $input,
                $output,
                $input->getOption(MagentoCloudOptions::SILENT_INSTALL)
            );
            $composerJsonExists = file_exists(sprintf('%s/composer.json', $magentoPath));
            if ($composerJsonExists) {
                $this->executeCommands(sprintf('cd %s && %s composer install', $magentoPath, $customAuth), $output);
            }
            XDebugSwitcher::switchOn();
        } else {
            $edition = strtolower($this->requestOption(MagentoOptions::EDITION, $input, $output)) == 'ee'
                ? 'enterprise'
                : 'community';
            $version = $this->requestOption(MagentoOptions::VERSION, $input, $output);
            $version = strlen($version) > 0 ? ':' . $version : '';

            XDebugSwitcher::switchOff();
            $this->executeCommands(
                [
                    sprintf(
                        'cd %s && %s composer create-project --repository-url=https://repo.magento.com/'
                        . ' magento/project-%s-edition%s .',
                        $magentoPath,
                        $customAuth,
                        $edition,
                        $version
                    )
                ],
                $output
            );
            XDebugSwitcher::switchOn();
        }

        if ($auth) {
            file_put_contents($magentoPath . '/auth.json', $auth);
        }

        if (!Registry::get(static::CHAINED_EXECUTION_FLAG)) {
            $output->writeln('To setup magento run <info>m2init magento:setup</info> command next');
        }

        Registry::set(MagentoOptions::SOURCES_REUSE, $useExistingSources);
    }

    /**
     * Download sources from Magento Cloud
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param bool $silentInstall
     * @return void
     * @throws \Exception
     */
    private function installFromCloud(InputInterface $input, OutputInterface $output, $silentInstall = false)
    {
        if (!$this->commandExist('magento-cloud')) {
            $this->executeCommands('php /home/magento2/installer', $output);
        }

        $command = $silentInstall ? '/home/magento2/scripts/bin/magento-cloud-login' : 'magento-cloud';
        $this->executeCommands($command, $output);

        if ($silentInstall) {
            $project = $input->getOption(MagentoCloudOptions::PROJECT);
            $branch = $input->getOption(MagentoCloudOptions::BRANCH);
        } else {
            $project = $this->requestProjectName($input, $output);
            $branch = $this->requestBranchName($input, $output, $project);
        }

        $keyName = $this->getSshKey($input, $output);

        chmod(sprintf('/home/magento2/.ssh/%s', $keyName), 0600);

        $this->executeCommands(
            sprintf('echo "IdentityFile /home/magento2/.ssh/%s" >> /etc/ssh/ssh_config', $keyName),
            $output
        );

        if ($this->sshKeyIsNew || $this->requestOption(MagentoCloudOptions::KEY_ADD, $input, $output)) {
            $this->executeCommands(
                [
                    sprintf('magento-cloud ssh-key:add --yes /home/magento2/.ssh/%s.pub', $keyName),
                    'magento-cloud ssh-key:list'
                ],
                $output
            );
        }

        $sshHost = $this->shellExec('magento-cloud environment:ssh --pipe -p ' . $project . ' -e ' . $branch);

        $command = sprintf(
            'ssh -q -o "BatchMode=yes" %s "echo 2>&1" && echo $host SSH_OK || echo $host SSH_NOK',
            $sshHost
        );

        $output->writeln($command);
        $attempt = 0;

        do {
            for ($i = 0; $i < $this->keysAvailabilityInterval; $i++) {
                $output->write('.');
                sleep(1);
            }
            $result = $this->shellExec($command);
        } while (trim($result) != 'SSH_OK' || $attempt++ > $this->maxAttemptsCount);

        $output->writeln("\n");

        if (trim($result) == 'SSH_OK') {
            $output->writeln('SSH connection with the Magento Cloud can be established.');
        } else {
            throw new \Exception(
                'You selected to init project from the Magento Cloud, but SSH connection cannot be established.'
                    . ' Please start from the beginning.'
            );
        }

        $this->executeCommands(
            sprintf(
                'git clone --branch %s %s@git.us.magento.cloud:%s.git %s',
                $branch,
                $project,
                $project,
                $input->getOption(MagentoOptions::PATH)
            ),
            $output
        );
    }

    /**
     * Wrapper for shell_exec
     *
     * @param $command
     * @return string
     */
    private function shellExec($command)
    {
        return shell_exec($command);
    }

    /**
     * Generate auth json config
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param bool $silentInstall
     * @return string
     */
    private function generateAuthConfig(InputInterface $input, OutputInterface $output, $silentInstall = false)
    {
        $config = '';
        $publicKey = $silentInstall
            ? $input->getOption(ComposerOptions::PUBLIC_KEY)
            : $this->requestOption(ComposerOptions::PUBLIC_KEY, $input, $output);
        $privateKey = $silentInstall
            ? $input->getOption(ComposerOptions::PRIVATE_KEY)
            : $this->requestOption(ComposerOptions::PRIVATE_KEY, $input, $output);
        if ($publicKey && $privateKey) {
            $config = sprintf(
                '{"http-basic": {"repo.magento.com": {"username": "%s", "password": "%s"}}}',
                $publicKey,
                $privateKey
            );
        }
        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            MagentoOptions::SOURCES_REUSE => MagentoOptions::get(MagentoOptions::SOURCES_REUSE),
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
            MagentoOptions::EDITION => MagentoOptions::get(MagentoOptions::EDITION),
            MagentoOptions::VERSION => MagentoOptions::get(MagentoOptions::VERSION),
            MagentoCloudOptions::INSTALL => MagentoCloudOptions::get(MagentoCloudOptions::INSTALL),
            MagentoCloudOptions::SILENT_INSTALL => MagentoCloudOptions::get(MagentoCloudOptions::SILENT_INSTALL),
            MagentoCloudOptions::KEY_REUSE => MagentoCloudOptions::get(MagentoCloudOptions::KEY_REUSE),
            MagentoCloudOptions::KEY_CREATE => MagentoCloudOptions::get(MagentoCloudOptions::KEY_CREATE),
            MagentoCloudOptions::KEY_NAME => MagentoCloudOptions::get(MagentoCloudOptions::KEY_NAME),
            MagentoCloudOptions::KEY_SWITCH => MagentoCloudOptions::get(MagentoCloudOptions::KEY_SWITCH),
            MagentoCloudOptions::KEY_ADD => MagentoCloudOptions::get(MagentoCloudOptions::KEY_ADD),
            MagentoCloudOptions::PROJECT => MagentoCloudOptions::get(MagentoCloudOptions::PROJECT),
            MagentoCloudOptions::PROJECT_SKIP => MagentoCloudOptions::get(MagentoCloudOptions::PROJECT_SKIP),
            MagentoCloudOptions::BRANCH => MagentoCloudOptions::get(MagentoCloudOptions::BRANCH),
            MagentoCloudOptions::BRANCH_SKIP => MagentoCloudOptions::get(MagentoCloudOptions::BRANCH_SKIP),
            ComposerOptions::PUBLIC_KEY => ComposerOptions::get(ComposerOptions::PUBLIC_KEY),
            ComposerOptions::PRIVATE_KEY => ComposerOptions::get(ComposerOptions::PRIVATE_KEY),
            MagentoOptions::STATE_PATH => MagentoOptions::get(MagentoOptions::STATE_PATH),
            MagentoOptions::ENABLE_SYNC_MARKER => MagentoOptions::get(MagentoOptions::ENABLE_SYNC_MARKER)
        ];
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     * @throws \Exception
     */
    private function requestProjectName(InputInterface $input, OutputInterface $output)
    {
        $this->executeCommands('magento-cloud project:list', $output);
        $project = $this->requestOption(MagentoCloudOptions::PROJECT, $input, $output);

        while (!$project) {
            if ($this->requestOption(MagentoCloudOptions::PROJECT_SKIP, $input, $output, true)) {
                $project = $this->requestOption(MagentoCloudOptions::PROJECT, $input, $output, true);
            } else {
                throw new \Exception(
                    'You selected to init project from the Magento Cloud, but haven\'t provided project name.'
                    . ' Please start from the beginning.'
                );
            }
        }
        return $project;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $project
     * @return string
     * @throws \Exception
     */
    private function requestBranchName(InputInterface $input, OutputInterface $output, $project)
    {
        $this->executeCommands('magento-cloud environment:list --project=' . $project, $output);
        $branch = $this->requestOption(MagentoCloudOptions::BRANCH, $input, $output);

        while (!$branch) {
            if ($this->requestOption(MagentoCloudOptions::BRANCH_SKIP, $input, $output, true)) {
                $branch = $this->requestOption(MagentoCloudOptions::BRANCH, $input, $output, true);
            } else {
                throw new \Exception(
                    'You selected to init project from the Magento Cloud, but haven\'t provided branch name.'
                    . ' Please start from the beginning.'
                );
            }
        }
        return $branch;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     * @throws \Exception
     */
    private function getSshKey(InputInterface $input, OutputInterface $output)
    {
        if ($this->requestOption(MagentoCloudOptions::KEY_REUSE, $input, $output)) {
            $keyName = $this->requestOption(MagentoCloudOptions::KEY_NAME, $input, $output);

            while (!file_exists(sprintf('/home/magento2/.ssh/%s', $keyName))) {
                if ($this->requestOption(MagentoCloudOptions::KEY_SWITCH, $input, $output, true)) {
                    $keyName = $this->requestOption(MagentoCloudOptions::KEY_NAME, $input, $output, true);
                } else {
                    if ($this->requestOption(MagentoCloudOptions::KEY_CREATE, $input, $output)) {
                        $keyName = $this->requestOption(
                            MagentoCloudOptions::KEY_NAME,
                            $input,
                            $output,
                            true,
                            'New SSH key will be generated and saved to the local file. Enter the name for local file'
                        );

                        $this->executeCommands(
                            sprintf('ssh-keygen -t rsa -N "" -f /home/magento2/.ssh/%s', $keyName),
                            $output
                        );
                        $this->sshKeyIsNew = true;
                    } else {
                        throw new \Exception(
                            'You selected to init project from the Magento Cloud,'
                            . ' but SSH key for the Cloud is missing. Start from the beginning.'
                        );
                    }
                }
            }
            return $keyName;
        } else {
            $keyName = $this->requestOption(
                MagentoCloudOptions::KEY_NAME,
                $input,
                $output,
                false,
                'New SSH key will be generated and saved to the local file. Enter the name for local file'
            );

            $this->executeCommands(sprintf('ssh-keygen -t rsa -N "" -f /home/magento2/.ssh/%s', $keyName), $output);
            $this->sshKeyIsNew = true;
            return $keyName;
        }
    }
}
