<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command;

use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use MagentoDevBox\Library\Registry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Command for Magento final steps
 */
class MagentoInstall extends AbstractCommand
{
    /**
     * @var array
     */
    private $optionsConfig;

    /**
     * @var array
     */
    private $sharedData = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:install')
            ->setDescription('Setup Magento and all components')
            ->setHelp('This command allows you to setup Magento and all components.');
    }

    /**
     * Perform delayed configuration
     *
     * @return void
     */
    public function postConfigure()
    {
        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws CommandNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Registry::set(static::CHAINED_EXECUTION_FLAG, true);

        $this->executeWrappedCommands(
            [
                'magento:download',
                'magento:setup',
                'magento:setup:varnish',
                'magento:setup:redis',
                'magento:setup:elasticsearch',
                'magento:setup:integration-tests',
                'magento:finalize',
            ],
            $input,
            $output
        );

        if (
            Registry::hasAll(
                [
                    MagentoOptions::HOST, MagentoOptions::PORT,
                    MagentoOptions::BACKEND_PATH, MagentoOptions::ADMIN_USER, MagentoOptions::ADMIN_PASSWORD
                ]
            )
        ) {
            $magentoUrl = sprintf(
                'http://%s:%s',
                Registry::get(MagentoOptions::HOST),
                Registry::get(MagentoOptions::PORT)
            );

            $output->writeln(
                sprintf(
                    'To open installed magento go to <info>%s</info>, admin area: <info>%s/%s</info>,'
                        . ' login: <info>%s</info>, password: <info>%s</info>',
                    $magentoUrl,
                    $magentoUrl,
                    Registry::get(MagentoOptions::BACKEND_PATH),
                    Registry::get(MagentoOptions::ADMIN_USER),
                    Registry::get(MagentoOptions::ADMIN_PASSWORD)
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        $optionsConfig = [];

        if ($this->optionsConfig === null) {
            //Merge configs of all commands into this command wrapper so input passes validation
            /** @var AbstractCommand $command */
            foreach ($this->getApplication()->all() as $command) {
                if ($command instanceof AbstractCommand && !$command instanceof self) {
                    $optionsConfig = array_replace($optionsConfig, $command->getOptionsConfig());
                }
            }

            //Make sure this command does not request any values
            foreach ($optionsConfig as $optionName => $optionConfig) {
                $optionsConfig[$optionName]['initial'] = false;
            }

            //Cache config
            $this->optionsConfig = $optionsConfig;
        }

        return $this->optionsConfig;
    }

    /**
     * Execute wrapped commands
     *
     * @param array|string $commandNames
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws CommandNotFoundException
     */
    private function executeWrappedCommands($commandNames, InputInterface $input, OutputInterface $output)
    {
        $commandNames = (array)$commandNames;

        foreach ($commandNames as $commandName) {
            $this->executeWrappedCommand($commandName, $input, $output);
        }
    }

    /**
     * Execute wrapped command
     *
     * @param string $commandName
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws CommandNotFoundException
     */
    private function executeWrappedCommand($commandName, InputInterface $input, OutputInterface $output)
    {
        /** @var AbstractCommand $command */
        $command = $this->getApplication()->get($commandName);
        $arguments = [null, $commandName];

        //Set values for options supported by the current command
        foreach ($command->getOptionsConfig() as $optionName => $optionConfig) {
            //Only if option is not virtual (defined as a valid CLI option)
            //And only if value was passed through CLI or was set in one of previously executed commands
            if (!$this->getConfigValue('virtual', $optionConfig, false)
                && ($input->hasParameterOption('--' . $optionName) || array_key_exists($optionName, $this->sharedData))
            ) {
                //Value set in previously executed command overwrites value originally passed through CLI
                $optionValue = array_key_exists($optionName, $this->sharedData)
                    ? $this->sharedData[$optionName]
                    : $input->getOption($optionName);

                //Value transformation for boolean type
                if ($this->getConfigValue('boolean', $optionConfig, false)) {
                    $optionValue = $optionValue ? static::SYMBOL_BOOLEAN_TRUE : static::SYMBOL_BOOLEAN_FALSE;
                }

                $arguments[] = sprintf('--%s=%s', $optionName, $optionValue);
            }
        }

        //Manually create new input for the command so it passes validation
        $commandInput = new ArgvInput($arguments);
        $commandInput->setInteractive($input->isInteractive());
        $command->run($commandInput, $output);

        //Store values that were set during current command execution for future commands
        foreach ($command->getValueSetStates() as $optionName => $optionState) {
            if ($optionState) {
                $this->sharedData[$optionName] = $commandInput->getOption($optionName);
            }
        }
    }
}
