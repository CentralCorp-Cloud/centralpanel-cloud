<?php

namespace Tests\Unit;

use App\Support\YouTube;
use PHPUnit\Framework\TestCase;

class YouTubeTest extends TestCase
{
    public function test_it_extracts_supported_youtube_video_ids(): void
    {
        $this->assertSame('dQw4w9WgXcQ', YouTube::videoId('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', YouTube::videoId('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', YouTube::videoId('https://www.youtube.com/shorts/dQw4w9WgXcQ'));
        $this->assertNull(YouTube::videoId('https://example.com/not-youtube'));
        $this->assertSame('short', YouTube::type('https://www.youtube.com/shorts/dQw4w9WgXcQ'));
    }
}
