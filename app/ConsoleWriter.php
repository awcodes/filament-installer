<?php

namespace App;

use Illuminate\Console\OutputStyle;
use Symfony\Component\Process\Process;
use function Termwind\{render};

class ConsoleWriter extends OutputStyle
{
    public array $styles = [
        'default' => 'bg-gray-800',
        'success' => 'bg-green-500 text-black',
        'danger' => 'bg-red-500',
        'warning' => 'bg-amber-500 text-black',
        'info' => 'bg-sky-500',
    ];

    public static function formatString(string $string, string $format): string
    {
        return "<{$format}>{$string}</>";
    }

    public function panel(string $message, string $style = 'default')
    {
        render(<<<HTML
            <div class="p-1 mt-1 w-full text-center {$this->styles[$style]}">
                {$message}
            </div>
        HTML);
    }

    public function sectionTitle($sectionTitle)
    {
        render(<<<HTML
            <div class="ml-1 px-1 bg-green-300 text-black">
                {$sectionTitle}
            </div>
        HTML);
    }

    public function sectionSubTitle($sectionSubTitle)
    {
        render(<<<HTML
            <div class="px-1 mt-1 font-bold">
                {$sectionSubTitle}
            </div>
        HTML);
    }

    public function logStep($message)
    {
        render(<<<HTML
            <div class="mt-1 ml-1">
                <em class="text-yellow-500">
                    {$message}
                </em>
            </div>
        HTML);
    }

    public function exec(string $command)
    {
        $this->labeledLine('EXEC', $command, 'info');
    }

    public function success($message, $label = 'PASS'): void
    {
        $this->labeledLine($label, $message, 'success');
    }

    public function ok($message): void
    {
        $this->success($message, ' OK ');
    }

    public function note($message, $label = 'NOTE'): void
    {
        $this->labeledLine($label, $message, 'warning');
    }

    public function warn($message, $label = 'WARN'): void
    {
        $this->labeledLine($label, $message, 'danger');
    }

    public function warnCommandFailed($command): void
    {
        $this->warn("Failed to run {$command}");
    }

    public function showOutputErrors(string $errors)
    {
        parent::text([
            '<fg=red;bg=default>--------------------------------------------------------------------------------',
            str_replace(PHP_EOL, PHP_EOL . ' ', trim($errors)),
            '--------------------------------------------------------------------------------</>',
        ]);
    }

    public function showOutput(string $errors)
    {
        parent::text([
            '--------------------------------------------------------------------------------',
            str_replace(PHP_EOL, PHP_EOL . ' ', trim($errors)),
            '--------------------------------------------------------------------------------',
        ]);
    }

    public function exception($message)
    {
        render(<<<HTML
            <div class="ml-1 px-1 bg-red-500 text-black">
                {$message}
            </div>
        HTML);
    }

    public function text($message)
    {
        parent::text($message);
    }

    public function listing(array $items): void
    {
        $itemsToString = '';
        foreach ($items as $item) {
            $itemsToString .= '<li class="pl-2">' . $item . '</li>';
        }

        render(<<<HTML
            <ol class="mt-1 ml-1">{$itemsToString}</ol>
        HTML);
    }

    public function table(array $columnHeadings, array $rowData)
    {
        parent::table($columnHeadings, $rowData);
    }

    public function consoleOutput(string $line, $type)
    {
        if (config('installer.store.with_output')) {
            ($type === Process::ERR)
                ? $this->consoleLabeledLine('!️', '┃ ' . $line, 'fg=yellow')
                : $this->consoleLabeledLine('✓︎', '┃ ' . $line, 'fg=green;');
        }
    }

    public function consoleLabeledLine(string $label, string $message, string $labelFormat = 'fg=default;bg=default', int $indentColumns = 0): void
    {
        $indent = str_repeat(' ', $indentColumns);
        $this->isDecorated()
            ? parent::text("{$indent}<{$labelFormat}> {$label} </> {$message}")
            : parent::text("{$indent}[ {$label} ] {$message}");
    }

    public function labeledLine(string $label, string $message, string $labelFormat = 'info'): void
    {
        render(<<<HTML
            <div class="mt-1 ml-1">
                <div class="px-1 text-black {$this->styles[$labelFormat]}">{$label}</div>
                <span class="ml-1">
                    {$message}
                </span>
            </div>
        HTML);
    }
}
