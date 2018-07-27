<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\SolrBundle\Console\Output;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * This act as an streamable wrapper around the symfony OutputInterface
 * so it can be used as an (writable) resource and redirects all write
 * calls to the OutputInterface::write method.
 *
 * To use this wrapper:
 *
 * StreamableOutputWrapper::init();     // will register the output protocol
 * $resource = fopen('output://', 'w', false, stream_context_create(['output'=>['writer' => $output]]));
 * fwrite($resource, 'foo');
 *
 * This wrapper also supports some post formatting, and that can be registered
 * by using the fmt context option. This should be an array with the pattern
 * as key and replacements as values. So for example to wrap everything in the
 * `info` color style:
 *
 * $ctx = tream_context_create(['output'=>['writer' => $output, 'fmt' => ['/^.+$/' => '<info>\0</info>']]])
 * $resource = fopen('output://', 'w', false, $ctx);
 * fwrite($resource, 'foo') // should print foo in green text
 *
 */
class StreamableOutputWrapper
{
    /** @var OutputInterface */
    private $output;
    /** @var resource */
    public $context;
    /** @var array  */
    private $fmt = [];

    /**
     * @param OutputInterface $output
     * @param array $fmt
     * @return bool|resource
     */
    public static function getResource(OutputInterface $output, array $fmt = [])
    {
        self::init();
        return fopen('output://', 'w', false, stream_context_create(['output'=>['writer' => $output, 'fmt' => $fmt]]));
    }

    public static function init()
    {
        if (!in_array('output', stream_get_wrappers())) {
            stream_wrapper_register('output', self::class);
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
        if (isset($options['output']['fmt'])) {
            $this->fmt = $options['output']['fmt'];
        }
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
        if (!empty($this->fmt)) {
            $data = preg_replace(array_keys($this->fmt), array_values($this->fmt), $data);
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
