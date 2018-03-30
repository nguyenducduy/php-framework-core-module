<?php
namespace Core\Command;

use Core\Command\BaseCommand;
use Shirou\Interfaces\ICommand;
use Shirou\Console\ConsoleUtil;

/**
 * @CommandName(['test'])
 * @CommandDescription('Test command controller.')
 */
class Test extends BaseCommand implements ICommand
{
    /**
     * Test action with params.
     *
     * @param string|null $param1 Param1 - string. Example: "string".
     * @param bool        $param2 Param2 is flag.
     *
     * @return void
     */
    public function testAction($param1 = null, $param2 = null)
    {
        print ConsoleUtil::success('Test command success - param1: '. $param1 .' - param2: '. $param2 .'.') . PHP_EOL;
    }
}
