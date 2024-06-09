<?php

namespace Pionia\codegens;

use Symfony\Component\Console\Output\OutputInterface;

abstract class CodeGenerator
{
    protected string $name;
    protected ?OutputInterface $output = null;

    /**
     * Spices up the name a little 'user' becomes 'UserService'
     * @param string $type
     * @return string
     */
    protected function sweetName(string $type): string
    {
        if (str_contains(strtolower($this->name), strtolower($type))) {
            return ucfirst($this->name);
        }
        return ucfirst($this->name) .$type;
    }

    abstract public function generate();

    protected function log(string $message): void
    {
        if ($this->output){
            $this->output->writeln($message);
        } else {
            echo $message;
        }
    }

    protected function getOrCreateDirectory(string $directory): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        return $directory;
    }

    protected function createFile(string $filename, string $content): void
    {
        $dir = dirname($filename);
        $check = $this->getOrCreateDirectory($dir);
        if ($check){
            file_put_contents($filename, $content);
        }
    }
}