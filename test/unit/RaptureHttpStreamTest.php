<?php

class RaptureHttpStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testFile()
    {
        $file = 'test.txt';
        $contents = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut finibus urna nec lobortis porta. Aenean eget mattis leo. Vestibulum pulvinar arcu ac mauris molestie iaculis. Suspendisse a nulla vel leo fermentum tempor. Donec turpis lacus, molestie a rutrum varius, tincidunt a erat. In at neque id magna vestibulum elementum mollis vel mauris. Nunc tincidunt, tortor ut lobortis bibendum, justo enim tristique nibh, nec pulvinar velit diam et justo.';

        file_put_contents($file, $contents);
        chmod($file, 0777);

        $stream = new \Rapture\Http\Stream(fopen($file, 'r+'));

        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isReadable());
        $this->assertEquals(446, $stream->getSize());
        $stream->rewind();
        $this->assertEquals(0, $stream->tell());
        $this->assertEquals(0, $stream->seek(446));
        $stream->rewind();
        $this->assertEquals('Lorem ipsum', $stream->read(11));
        $stream->rewind();
        $this->assertEquals($contents, $stream->getContents());
        $this->assertTrue($stream->isSeekable());

        // metadata
        $stream->rewind();
        $this->assertEquals([
            'wrapper_type' => 'plainfile',
            'stream_type' => 'STDIO',
            'mode' => 'r+',
            'unread_bytes' => 0,
            'seekable' => true,
            'uri' => 'test.txt',
            'timed_out' => false,
            'blocked' => true,
            'eof' => false,
        ], [
            'wrapper_type' => $stream->getMetadata('wrapper_type'),
            'stream_type' => $stream->getMetadata('stream_type'),
            'mode' => $stream->getMetadata('mode'),
            'unread_bytes' => $stream->getMetadata('unread_bytes'),
            'seekable' => $stream->getMetadata('seekable'),
            'uri' => $stream->getMetadata('uri'),
            'timed_out' => $stream->getMetadata('timed_out'),
            'blocked' => $stream->getMetadata('blocked'),
            'eof' => $stream->getMetadata('eof')
        ]);

        $stream->rewind();
        $this->assertEquals(0, $stream->getMetadata('unread_bytes'));
        $this->assertEquals(null, $stream->getMetadata('unknown-key'));
        $this->assertFalse($stream->eof());

        // write
        $stream->seek($stream->getSize());
        $this->assertEquals(12, $stream->write('hello world!'));
        $stream->seek(0);
        $this->assertEquals($contents.'hello world!', $stream->getContents());
        $this->assertEquals($contents.'hello world!', (string)$stream);

        unlink($file);
    }
}
