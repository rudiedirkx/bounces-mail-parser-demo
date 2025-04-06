<?php

use PhpMimeMailParser\Attachment;
use PhpMimeMailParser\Parser;
use ZBateson\MailMimeParser\Header\IHeader;
use ZBateson\MailMimeParser\Header\IHeaderPart;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\MimePart;

require __DIR__ . '/vendor/autoload.php';

$emlFiles = glob(__DIR__ . '/eml/*.eml');
// dump($emlFiles);
$emlFile = $emlFiles[array_rand($emlFiles)];
// $emlFile = 'eml/Undeliverable Nieuwe reservering (20-01-2025 om 16 30 op Padel indoor 2) ...464854.eml';
dump($emlFile);
$emlContent = file_get_contents($emlFile);

echo "\n<hr>\n\n";

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


$message = Message::from($emlContent, false);
// dump($message->getAllHeadersByName('to'));
dump($message->getHeaderValue('subject'));
$relevantParts = $message->getAllParts(function(MimePart $part) {
	return in_array($part->getContentType(), ['text/rfc822-headers', 'message/rfc822']);
});
dump($relevantParts);

foreach ($relevantParts as $part) {
	$subMessage = Message::from($part->getContent(), false);
	dump(
		$part->getContentType(),
		array_map(fn($hd) => $hd->getValue(), $subMessage->getAllHeadersByName('to')),
		$subMessage->getAllHeadersByName('to'),
		array_map(function(array $map) : null|string|array {
			return count($map) > 1 ? $map : $map[0];
		}, array_reduce($subMessage->getAllHeaders(), function(array $map, IHeader $hd) : array {
			$map[strtolower($hd->getName())][] = implode(', ', array_map(fn(IHeaderPart $p) => $p->getValue(), $hd->getParts()));
			return $map;
		}, [])),
		// array_combine(array_column($hds = $subMessage->getRawHeaders(), 0), array_column($hds, 1)),
	);
}


echo "\n<hr>\n\n";


$parser = MyParser::fromText($emlContent);
dump($parser->getHeader('subject'));
// dump($parser->getMessageBody());
// dump([...array_values($parser->getParts()), ...$parser->getAttachments(false)]);
dump(array_map(fn(array $part) => $part['headers']['content-type'] ?? '?', $parser->getParts()));
$relevantParts = $parser->getAttachmentsOfTypes(['text/rfc822-headers', 'message/rfc822']);
dump($relevantParts);

foreach ($relevantParts as $part) {
	$subParser = MyParser::fromText($part->getContent());
	dump(
		$part->getContentType(),
		array_column($subParser->getAddresses('to'), 'address'),
		$subParser->getHeaders(),
	);
}


echo "\n<hr>\n\n";

dump($emlContent);
