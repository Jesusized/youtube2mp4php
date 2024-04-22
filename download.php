<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use YoutubeDl\Options;
use YoutubeDl\YoutubeDl;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $videoUrl = $_POST['video-url'];
  $format = $_POST['format'];

  $yt = new YoutubeDl();
  $yt->onProgress(function (?string $progressTarget, string $percentage, string $size, string $speed, string $eta, ?string $totalTime) {
    $progress = [
      'percentage' => $percentage,
      'size' => $size,
      'speed' => $speed,
      'eta' => $eta,
      'totalTime' => $totalTime,
    ];
    echo json_encode($progress);
    ob_flush();
    flush();
  });

  $options = Options::create()
    ->downloadPath(__DIR__ . '/downloads')
    ->url($videoUrl);

  if ($format === 'mp4') {
    $options->format('bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best');
  } elseif ($format === 'mp3') {
    $options->extractAudio(true)
      ->audioFormat('mp3')
      ->audioQuality('0');
  }

  $collection = $yt->download($options);

  foreach ($collection->getVideos() as $video) {
    if ($video->getError() !== null) {
      echo "Error downloading video: {$video->getError()}.";
    } else {
      $file = $video->getFile();
      if ($file) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file->getBasename() . '"');
        header('Content-Length: ' . $file->getSize());
        readfile($file->getPathname());
        unlink($file->getPathname()); // Use unlink() instead of getUnlink()
      }
    }
  }
}
