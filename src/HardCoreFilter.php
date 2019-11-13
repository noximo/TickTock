<?php

final class HardCoreFilter extends php_user_filter
{
    const NAME = 'php-hardcode-filter';

    protected $buffer = '';
    public $stream;

    public static function append($resource, string $path)
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        stream_filter_append(
            $resource,
            self::NAME,
            STREAM_FILTER_READ,
            [
                'ext' => $ext,
                'path' => $path,
            ]
        );
    }

    public static function register()
    {
        stream_filter_register(self::NAME, static::class);
    }

    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $this->buffer .= $bucket->data;
            $consumed += $bucket->datalen;
        }
        if ($closing) {
            $buffer = $this->doFilter($this->buffer, $this->params['path'], $this->params['ext']);
            $bucket = stream_bucket_new($this->stream, $buffer);
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    private function doFilter($buffer, $path, $ext): string
    {
        if ('php' !== $ext) {
            return $buffer;
        }

        if (0 !== strpos($buffer, '<?php')) {
            return $buffer;
        }

        $buffer = str_replace('<?php', '<?php declare(ticks=1);', $buffer);

        return $buffer;
    }
}
