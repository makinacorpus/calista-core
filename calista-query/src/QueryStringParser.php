<?php

namespace MakinaCorpus\Calista\Query;

/**
 * Search query parser, with a really simple syntax.
 *
 * Syntax is you can repeat as many time this:
 *   FIELD:"VALUE"
 *
 * Where:
 *   - FIELD: is optional, without it means full text search on the
 *     query;
 *   - Quotes "" are optional, but removing it you cannot set space
 *     in you values;
 *   - Spaces between quotes don't act such as a value separator, but
 *     are part of the value;
 *   - You can repeat the same field more than once, meaning you filter
 *     accepting all the values given;
 *   - That's pretty much it.
 *
 * When the same field appear more than once, you their act as OR, aside
 * of that, all fields acts together as an AND statement. Parenthesis
 * have no meaning whatsoever, they are part of values.
 */
final class QueryStringParser
{
    const REGEX_QUERY_STRING = '@
        (?:([\w\-\_]+)\:|)  # "FIELD:" matching
        (
            \".*\"          # Anything between quotes (ungreedy)
            |
            [^\s]+?         # Anything that is not space (greedy)
        )
        @xmuU'
    ;

    /**
     * Parse query string
     *
     * @param string $string
     * @param string $searchParameter
     *   Treat differently parsed values with this name, and let exists as
     *   a raw string for full text search
     *
     * @return string[][]
     *   Query filter, keys are field names, values are arrays of string
     *   values.
     */
    public function parse(string $string, string $searchParameter): array
    {
        $ret = [];

        $matches = [];
        if (preg_match_all(self::REGEX_QUERY_STRING, $string, $matches)) {

            foreach ($matches[1] as $index => $field) {

                if (!$field) {
                    $field = $searchParameter;
                }

                $value = $matches[2][$index];

                // Strip extra quotes but allow inside quotes
                $len = strlen($value);
                if ('"' === $value[0] && '"' === $value[$len - 1]) {
                    $value = substr($value, 1, $len - 2);
                }

                // Remove all trailing and leading spaces.
                $value = trim($value);

                // Broken value
                if ('' === $value) {
                    continue;
                }

                $ret[$field][] = $value;
            }
        }

        return $ret;
    }
}
