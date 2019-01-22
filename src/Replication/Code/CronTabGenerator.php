<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use Ls\Core\Code\AbstractGenerator;
use Ls\Omni\Service;

/**
 * Class CronTabGenerator
 * @package Ls\Replication\Code
 */
class CronTabGenerator
{

    /**
     * @param Service\Metadata $metadata
     */
    public static function Generate(Service\Metadata $metadata)
    {
        $dom = new \DOMDocument('1.0');
        $dom->formatOutput = true;

        $config = $dom->createElement('config');
        $config->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $config->setAttribute('xsi:noNamespaceSchemaLocation', 'urn:magento:module:Magento_Cron:etc/crontab.xsd');

        $group = $dom->createElement('group');
        $group->setAttribute('id', 'replication');

        $jobNames = [];

        $cronminute = 1;


        foreach ($metadata->getOperations() as $operation_name => $operation) {
            if ($cronminute >= 59) {
                $cronminute = 1;
            }
            $cronminute = $cronminute + 2;

            if (strpos($operation_name, 'ReplEcomm') !== false) {
                $jobName = $metadata->getReplicationOperationByName($operation->getName())->getJobName();
                $jobId = strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', str_replace('Task', '', $jobName)));

                // Only unique JOB ID will be added inside crontab.xml
                if (!in_array($jobName, $jobNames)) {
                    $job = $dom->createElement('job');
                    $job->setAttribute('name', $jobId);
                    $job->setAttribute('instance', 'Ls\\Replication\\Cron\\' . $jobName);
                    $job->setAttribute('method', 'execute');

                    $schedule = $dom->createElement('schedule');
                    $schedule->appendChild($dom->createTextNode(" $cronminute * * * *"));

                    $job->appendChild($schedule);
                    $group->appendChild($job);
                    array_push($jobNames, $jobName);
                }
            }
        }

        $config->appendChild($group);
        $dom->appendChild($config);
        $dom->save(CronTabGenerator::getCronTabPath(true));
    }

    /**
     * @param bool $absolute
     * @return string $path
     */
    private static function getCronTabPath($absolute = false)
    {
        $path = AbstractGenerator::path('etc', 'crontab.xml');
        $base_path = CronTabGenerator::getPath();

        if ($absolute) {
            $path = AbstractGenerator::path($base_path, $path);
        }

        return $path;
    }

    /**
     * @return string
     */
    private static function getPath()
    {
        return CronTabGenerator::getModuleDirectory();
    }

    /**
     * @param string $moduleName
     * @param string $type
     * @return string
     */
    private static function getModuleDirectory($moduleName = 'Ls_Replication', $type = '')
    {
        /** @var \Magento\Framework\ObjectManagerInterface $om */
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\Module\Dir\Reader $reader */
        $reader = $om->get('Magento\Framework\Module\Dir\Reader');
        return $reader->getModuleDir($type, $moduleName);
    }
}
