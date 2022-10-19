<?php

namespace App\Commands;

use App\ConsoleWriter;
use Illuminate\Console\OutputStyle;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class InstallerCommand extends Command
{
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $io = new OutputStyle($input, $output);

        app()->singleton(ConsoleWriter::class, function () use ($input, $output) {
            return new ConsoleWriter($input, $output);
        });

        app()->alias(ConsoleWriter::class, 'console-writer');

        return parent::run($input, $output);
    }
}
