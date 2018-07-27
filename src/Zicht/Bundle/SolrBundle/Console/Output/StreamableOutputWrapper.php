<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Http;

use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Http\Stream\ResourceStream;

class ConsoleStream
{
    /** @var OutputInterface */
    private $output;
    /** @var resource */
    public $context;
    /** @var string  */
    private $tmpl;

    public static function init()
    {
        if (!in_array('output', stream_get_wrappers())) {
            stream_wrapper_register('output', self::class);
            stream_context_set_default(
                [
                    'output' => [
                        'tmpl' => [
                            '* ' => '<comment>%s</comment>',
                            '>>' => '<info>%s</info>',
                        ]
                    ]
                ]
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $options = $this->getContext();
        if (!isset($options['output']['writer'])) {
            throw new \RuntimeException('Missing output in wrapper context.');
        }
        if (!$options['output']['writer'] instanceof OutputInterface) {
            throw new \RuntimeException(sprintf(
                'Output writer should be an instance of %s, and %s was given',
                OutputInterface::class,
                is_object($options['output']['writer'])? get_class($options['output']['writer']) : gettype($options['output']['writer'])
            ));
        }
        $this->output = $options['output']['writer'];
        $this->tmpl = $options['output']['tmpl'];;
        return true;
    }

    /**
     * @return array
     */
    private function getContext()
    {
        $options = stream_context_get_options(stream_context_get_default());
        foreach (stream_context_get_options($this->context) as $key => $ctx) {
            if (isset($options[$key])) {
                $options[$key] = array_merge($options[$key], $ctx);
            } else {
                $options[$key] = $ctx;
            }
        }
        return $options;
    }

    /**
     * @inheritdoc
     */
    public function stream_write($data)
    {
        $prefix = @substr($data, 0, 2);

        if (isset($this->tmpl[$prefix])) {
            $data = sprintf($this->tmpl[$prefix], $data);
        }

        $this->output->write($data);
    }

    /**
     * @inheritdoc
     */
    public function stream_eof()
    {
        return false;
    }
}
