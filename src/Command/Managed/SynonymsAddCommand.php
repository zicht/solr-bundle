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
use Zicht\Bundle\SolrBundle\Entity\Synonym;
use Zicht\Bundle\SolrBundle\Manager\Doctrine\SynonymSubscriber;
use Zicht\Bundle\SolrBundle\Manager\SynonymManager;
use Zicht\Bundle\SolrBundle\Solr\Client;

class SynonymsAddCommand extends AbstractCommand
{
    const TYPE_CSV = 'csv';

    const TYPE_JSON = 'json';

    const TYPE_YAML = 'yaml';

    const TYPES = [self::TYPE_CSV, self::TYPE_JSON, self::TYPE_YAML];

    /** @var SynonymManager */
    protected $manager;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var SynonymSubscriber */
    protected $synonymSubscriber;

    /** @var Synonym[][] */
    protected $existingSynonyms = [];

    public function __construct(Client $solr, SynonymManager $manager, EntityManagerInterface $entityManager, SynonymSubscriber $synonymSubscriber)
    {
        parent::__construct($solr);

        $this->manager = $manager;
        $this->entityManager = $entityManager;
        $this->synonymSubscriber = $synonymSubscriber;
    }

    protected function configure()
    {
        $name = 'zicht:solr:synonyms-add';
        $description = 'Add synonyms to SOLR';
        $typeDescription = sprintf('The data type of the input <comment>[%s]</comment>', implode(', ', self::TYPES));
        $this
            ->setName($name)
            ->setDescription($description)
            ->addArgument('managed', InputArgument::REQUIRED, 'Specify the managed prefix')
            ->addArgument('synonyms', InputArgument::REQUIRED, 'The synonyms data')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, $typeDescription, self::TYPE_JSON)
            ->addOption('file', 'f', InputOption::VALUE_NONE, 'Treat the synonyms argument as a file containing the data instead of command line data input')
            ->setHelp(
                <<<HELP
<fg=yellow;options=bold>{$description}</>

You can import synonyms into SOLR + database using different formats and either directly from the commandline or from a file.

From the command line, as JSON:
<info>php app/console {$name} --type json 'en' '{"monkey": ["baboon", "capuchin", "gorilla", "macaque"], "horse": ["stallion", "mare"]}'</info>

From the command line, as CSV (the first column is the main word, all the following are the synonyms):
<info>php app/console {$name} --type csv 'en' 'monkey, baboon, capuchin, gorilla, macaque
horse, stallion, mare'</info>

To import data from a file, use the <comment>--file</comment> option. This makes the command interpret the <comment>synonyms</comment> argument as the file source for the data:
<info>php app/console {$name} --type json --file 'en' fixtures/synonyms_animals_en.json</info>
<info>php app/console {$name} --type csv --file 'en' fixtures/synonyms_animals_en.csv</info>
<info>php app/console {$name} --type yaml --file 'en' fixtures/synonyms_animals_en.yml</info>

HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Adding synonyms to SOLR');
        $output->writeln('');

        $managed = $input->getArgument('managed');

        if (true === $input->getOption('file')) {
            $data = file_get_contents($input->getArgument('synonyms'));
        } else {
            $data = $input->getArgument('synonyms');
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

        $this->synonymSubscriber->setEnabled(false);

        foreach ($data as $word => $synonyms) {
            $output->writeln(sprintf('- %s: %s', $word, implode(', ', $synonyms)));

            $synonym = $this->createSynonymEntity($managed, $word, $synonyms);

            $this->entityManager->persist($synonym);
            $data[$word] = $synonym;
        }
        $this->entityManager->flush();

        $this->synonymSubscriber->setEnabled(true);

        $this->manager->addSynonyms($data);
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
            $values = preg_split('/ *, */', $line, -1, PREG_SPLIT_NO_EMPTY);
            $word = array_shift($values);
            $newData[$word] = $values;
        }

        return $newData;
    }

    /**
     * @param string $managed
     * @param string $word
     * @param string[] $synonyms
     * @return Synonym
     */
    protected function createSynonymEntity($managed, $word, $synonyms)
    {
        if (empty($this->existingSynonyms)) {
            foreach ($this->entityManager->getRepository(Synonym::class)->findAll() as $existingSynonym) {
                if (!array_key_exists($existingSynonym->getManaged(), $this->existingSynonyms)) {
                    $this->existingSynonyms[$existingSynonym->getManaged()] = [];
                }

                $this->existingSynonyms[$existingSynonym->getManaged()][$existingSynonym->getIdentifier()] = $existingSynonym;
            }
        }

        if (isset($this->existingSynonyms[$managed][$word])) {
            $synonym = $this->existingSynonyms[$managed][$word];
            $synonym->setValue(implode(PHP_EOL, $synonyms));
        } else {
            $synonym = new Synonym($managed, $word, implode(PHP_EOL, $synonyms));
        }

        return $synonym;
    }
}
