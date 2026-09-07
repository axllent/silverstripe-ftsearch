<?php

namespace Axllent\FTSearch\Tasks;

use Axllent\FTSearch\SearchEngine;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Rebuilds FTSearch table.
 */
class RebuildFTSearchDB extends BuildTask
{
    /**
     * Build task command name
     */
    protected static string $commandName = 'RebuildFTSearchDB';

    /**
     * Build task title
     */
    protected string $title = 'Rebuild FTSearch Table';

    /**
     * Build task description
     */
    protected static string $description = 'Empties & rebuilds the entire FTSearch database table';

    /**
     * @var array
     */
    protected $completed = [];

    /**
     * Execute the build task
     */
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $data_objects    = Config::inst()->get(SearchEngine::class, 'data_objects');
        $exclude_classes = Config::inst()->get(SearchEngine::class, 'exclude_classes');

        DB::query('TRUNCATE FTSearch');

        $output->writeln('Truncated FTSearch');

        $total = 0;

        foreach ($data_objects as $class => $fields) {
            $objects = $class::get();
            foreach ($objects as $obj) {
                if ($obj->hasMethod('updateFTSearch') && !in_array($obj->ClassName, $exclude_classes)) {
                    $unique_id = md5($obj->ID . '-' . $obj->ClassName);
                    if (in_array($unique_id, $this->completed)) {
                        continue;
                    }
                    $this->completed[] = $unique_id;
                    $obj->updateFTSearch();
                    ++$total;
                }
            }
        }

        $output->writeln('Inserted ' . $total . ' records.');

        return Command::SUCCESS;
    }
}
