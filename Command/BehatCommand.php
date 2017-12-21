<?php

namespace Sfynx\BehatBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Sfynx\BehatBundle\Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\ApplicationFactory;

/**
 * Behat command with additional options (--server, --locale)
 */
class BehatCommand extends ContainerAwareCommand
{
    /**
     * Behat additional options
     * @var array $options
     */
    public static $options = array('server', 'locale');

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sfynx:behat:execute')
            ->setDescription('Call Behat with additional options.')
            ->setHelp(<<<EOT
The <info>sfynx:behat:execute</info> command to execute behat command

An example of usage of the command:

<info>./app/console sfynx:behat:execute</info>

EOT
            );
        foreach (self::$options as $option) {
            $this->addOption($option, null, InputOption::VALUE_OPTIONAL, 'Website '.$option.'.');
        }
        $this->addOption('suite', null, InputOption::VALUE_OPTIONAL, 'Specify a test suite to execute.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        MinkContext::$allowed = array(
            'servers' => $this->getContainer()->getParameter('behat.servers'),
            'locales' => $this->getContainer()->getParameter('behat.locales')
        );
        MinkContext::$options = $this->getContainer()->getParameter('behat.options');
        foreach (self::$options as $option) {
            if ($input->hasParameterOption('--'.$option)) {
                MinkContext::$options[$option] = $input->getParameterOption('--'.$option);
            }
        }
        $args = [];
        if ($input->hasParameterOption('--suite')) {
            $args['--suite'] = $input->getParameterOption('--suite');
        }
        $this->runBehatCommand($args);
    }

    /**
     * Run behat original command
     *
     * @param array $args
     */
    protected function runBehatCommand(array $args = array())
    {
        define('BEHAT_BIN_PATH', $this->getContainer()->getParameter('kernel.root_dir').'/../bin/behat');
        function includeIfExists($file)
        {
            if (file_exists($file)) {
                return include $file;
            }
        }
        if ((!$loader = includeIfExists($this->getContainer()->getParameter('kernel.root_dir').'/../vendor/autoload.php'))
                && (!$loader = includeIfExists($container->getParameter('kernel.root_dir').'/../../../../autoload.php'))
        ) {
            fwrite(STDERR,
                'You must set up the project dependencies, run the following commands:'.PHP_EOL.
                'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
                'php composer.phar install'.PHP_EOL
            );
            exit(1);
        }
        $factory = new ApplicationFactory();
        $factory->createApplication()->run(new ArrayInput($args));
    }
}
