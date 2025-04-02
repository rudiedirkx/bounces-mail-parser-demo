<?php

use PhpMimeMailParser\Attachment;
use PhpMimeMailParser\Parser;

require __DIR__ . '/vendor/autoload.php';

$emlFiles = glob(__DIR__ . '/eml/*.eml');
// dump($emlFiles);
$emlFile = $emlFiles[array_rand($emlFiles)];
// $emlFile = 'eml/Warning message 1tWLyC-00A3ZR-If delayed 72 hours.eml';
// $emlFile = 'eml/Undeliverable FW New reservation.eml';
dump($emlFile);
$emlContent = file_get_contents($emlFile);

class MyParser extends Parser {
	public function getAttachmentsOfTypes(array $types) : array {
		$types = array_map(strtolower(...), $types);
		return array_filter($this->getAttachments(false), function(Attachment $attachment) use ($types) {
			return in_array(strtolower($attachment->getContentType()), $types);
		});
	}

	static public function fromText(string $text) {
		$parser = new static();
		$parser->setText($text);
		return $parser;
	}
}

$parser = MyParser::fromText($emlContent);
dump($parser->getHeader('subject'));
// dump($parser->getMessageBody());
// dump([...array_values($parser->getParts()), ...$parser->getAttachments(false)]);
dump(array_map(fn(array $part) => $part['headers']['content-type'] ?? '?', $parser->getParts()));
$relevantParts = $parser->getAttachmentsOfTypes(['text/rfc822-headers', 'message/rfc822']);
dump($relevantParts);
foreach ($relevantParts as $part) {
	$subparser = MyParser::fromText($part->getContent());
	dump($part->getContentType(), array_column($subparser->getAddresses('to'), 'address'), $subparser->getHeaders());
}

dump($emlContent);
