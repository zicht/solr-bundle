<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Command\Managed;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Zicht\Bundle\SolrBundle\Command\AbstractCommand;
use Zicht\Bundle\SolrBundle\Entity\StopWord;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\StopWordSubscriber;
use Zicht\Bundle\SolrBundle\Manager\StopWordManager;
use Zicht\Bundle\SolrBundle\Solr\Client;

class StopWordsAddCommand extends AbstractCommand
{
    const TYPE_CSV = 'csv';

    const TYPE_JSON = 'json';

    const TYPE_YAML = 'yaml';

    const TYPES = [self::TYPE_CSV, self::TYPE_JSON, self::TYPE_YAML];

    /** @var StopWordManager */
    protected $manager;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var StopWordSubscriber */
    protected $stopWordSubscriber;

    /** @var StopWord[][] */
    protected $existingStopWords = [];

    public function __construct(Client $solr, StopWordManager $manager, EntityManagerInterface $entityManager, StopWordSubscriber $stopWordSubscriber)
    {
        parent::__construct($solr);

        $this->manager = $manager;
        $this->entityManager = $entityManager;
        $this->stopWordSubscriber = $stopWordSubscriber;
    }

    protected function configure()
    {
        $name = 'zicht:solr:stop-words-add';
        $description = 'Add stop words to SOLR';
        $typeDescription = sprintf('The data type of the input <comment>[%s]</comment>', implode(', ', self::TYPES));
        $this
            ->setName($name)
            ->setDescription($description)
            ->addArgument('managed', InputArgument::REQUIRED, 'Specify the managed prefix')
            ->addArgument('stop-words', InputArgument::REQUIRED, 'The stop words data')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, $typeDescription, self::TYPE_JSON)
            ->addOption('file', 'f', InputOption::VALUE_NONE, 'Treat the stop words argument as a file containing the data instead of command line data input')
            ->setHelp(
                <<<HELP
<fg=yellow;options=bold>{$description}</>

You can import stop words into SOLR + database using different formats and either directly from the commandline or from a file.

From the command line, as JSON:
<info>php app/console {$name} --type json 'en' '["a", "but", "the"]'</info>

From the command line, as "CSV" (one word per line):
<info>php app/console {$name} --type csv 'en' 'a
but
the'</info>

To import data from a file, use the <comment>--file</comment> option. This makes the command interpret the <comment>stop-words</comment> argument as the file source for the data:
<info>php app/console {$name} --type json --file 'en' fixtures/stop_words_en.json</info>
<info>php app/console {$name} --type csv --file 'en' fixtures/stop_words_en.csv</info>
<info>php app/console {$name} --type yaml --file 'en' fixtures/stop_words_en.yml</info>

HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Adding stop words to SOLR');
        $output->writeln('');

        $managed = $input->getArgument('managed');

        if (true === $input->getOption('file')) {
            $data = file_get_contents($input->getArgument('stop-words'));
        } else {
            $data = $input->getArgument('stop-words');
        }

        switch ($input->getOption('type')) {
            case self::TYPE_CSV:
                $data = $this->parseCsvInput($data);
                break;
            case self::TYPE_JSON:
                $data = \json_decode($data, true);
                break;
            case self::TYPE_YAML:
                if (!class_exists(Yaml::class, false)) {
                    throw new \RuntimeException(sprintf('Could not find %s. Please require symfony/yaml.', Yaml::class));
                }
                $data = Yaml::parse($data);
                break;
            default:
                throw new InvalidArgumentException('Type must be one of ' . implode(', ', self::TYPES));
        }

        $this->stopWordSubscriber->setEnabled(false);

        foreach ($data as $i => $word) {
            $output->writeln(sprintf('- %s', $word));

            $stopWord = $this->createStopWordEntity($managed, $word);

            $this->entityManager->persist($stopWord);
            $data[$i] = $stopWord;
        }
        $this->entityManager->flush();

        $this->stopWordSubscriber->setEnabled(true);

        $this->manager->addStopWords($data);
        $this->manager->getClient()->reload();

        return 0;
    }

    /**
     * @param string $data
     * @return array
     */
    protected function parseCsvInput($data)
    {
        $lines = explode(PHP_EOL, str_replace("\r", '', $data));
        $newData = [];
        foreach ($lines as $line) {
            if ('' === trim($line)) {
                continue;
            }
            $newData[] = $line;
        }

        return $newData;
    }

    /**
     * @param string $managed
     * @param string $word
     * @return StopWord
     */
    protected function createStopWordEntity($managed, $word)
    {
        if (empty($this->existingStopWords)) {
            foreach ($this->entityManager->getRepository(StopWord::class)->findAll() as $existingStopWord) {
                if (!array_key_exists($existingStopWord->getManaged(), $this->existingStopWords)) {
                    $this->existingStopWords[$existingStopWord->getManaged()] = [];
                }

                $this->existingStopWords[$existingStopWord->getManaged()][$existingStopWord->getValue()] = $existingStopWord;
            }
        }

        if (isset($this->existingStopWords[$managed][$word])) {
            $synonym = $this->existingStopWords[$managed][$word];
            $synonym->setValue($word);
        } else {
            $synonym = new StopWord($managed, $word);
        }

        return $synonym;
    }
}
