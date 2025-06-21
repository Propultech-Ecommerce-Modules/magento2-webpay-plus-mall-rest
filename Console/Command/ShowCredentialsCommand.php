<?php

namespace Propultech\WebpayPlusMallRest\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Propultech\WebpayPlusMallRest\Model\Config\ConfigProvider;
use  Transbank\Webpay\Model\Config\ConfigProvider as TbkConfigProvider;
class ShowCredentialsCommand extends Command
{

    /**
     * @param ConfigProvider $configProvider
     * @param TbkConfigProvider $tbkConfigProvider
     * @param string|null $name
     */
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly TbkConfigProvider $tbkConfigProvider,
        string $name = null
    ) {

        parent::__construct($name);
    }

    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('propultech:webpayplusmall:show-credentials')
            ->setDescription('Show WebpayPlusMall credentials in raw format');
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>WebpayPlusMall Credentials:</info>');
        $output->writeln('');

        // Get credentials from config provider
        $config = $this->configProvider->getPluginConfig();
        $tbkConfig = $this->tbkConfigProvider->getPluginConfig();
        $commerceCodes = $this->configProvider->getCommerceCodes();

        // Display transbank credentials
        $output->writeln('<comment>TBK Configuration:</comment>');
        $output->writeln('Environment: ' . $tbkConfig['ENVIRONMENT']);
        $output->writeln('Commerce Code: ' . $tbkConfig['COMMERCE_CODE']);
        $output->writeln('API Key: ' . $tbkConfig['API_KEY']);
        $output->writeln('');

        // Display main credentials
        $output->writeln('<comment>Main Configuration:</comment>');
        $output->writeln('Environment: ' . $config['ENVIRONMENT']);
        $output->writeln('Commerce Code: ' . $config['COMMERCE_CODE']);
        $output->writeln('API Key: ' . $config['API_KEY']);
        $output->writeln('');

        // Display commerce codes
        $output->writeln('<comment>Commerce Codes:</comment>');
        if (!empty($commerceCodes)) {
            foreach ($commerceCodes as $index => $codeData) {
                $output->writeln('Commerce Code ' . ($index + 1) . ':');
                foreach ($codeData as $key => $value) {
                    $output->writeln('  ' . $key . ': ' . $value);
                }
                $output->writeln('');
            }
        } else {
            $output->writeln('No commerce codes configured.');
            $output->writeln('');
        }

        return Command::SUCCESS;
    }
}
