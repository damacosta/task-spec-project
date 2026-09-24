<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\Docs;

use He4rt\PortalDocs\Support\Value;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads the YAML block an .mdx page opens with.
 *
 * The block must be the very first thing in the file: a leading blank line makes
 * MDX treat the dashes as a thematic break, and the page loses its title.
 */
final class FrontMatter
{
    private const string PATTERN = '/\A---\R(.*?)\R---\R?/s';

    /**
     * @return array<string, mixed>
     */
    public static function attributes(string $contents): array
    {
        if (preg_match(self::PATTERN, $contents, $matches) !== 1) {
            return [];
        }

        try {
            $parsed = Yaml::parse($matches[1]);
        } catch (ParseException) {
            return [];
        }

        return Value::map($parsed);
    }

    public static function strip(string $contents): string
    {
        return mb_ltrim((string) preg_replace(self::PATTERN, '', $contents));
    }
}
