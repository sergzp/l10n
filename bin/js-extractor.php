#!/usr/bin/php
<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

/**
 * js-extractor.php - rips gettext strings from Javascript ts() calls
 *
 * ------------------------------------------------------------------------- *
 * This library is free software; you can redistribute it and/or             *
 * modify it under the terms of the GNU Lesser General Public                *
 * License as published by the Free Software Foundation; either              *
 * version 2.1 of the License, or (at your option) any later version.        *
 *                                                                           *
 * This library is distributed in the hope that it will be useful,           *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of            *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU         *
 * Lesser General Public License for more details.                           *
 *                                                                           *
 * You should have received a copy of the GNU Lesser General Public          *
 * License along with this library; if not, write to the Free Software       *
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA *
 * ------------------------------------------------------------------------- *
 *
 * This command line script rips gettext strings from js file, and prints
 * them to stdout; this can later be used with the standard gettext tools.
 *
 * Usage:
 * ./js-extractor.php <filename or directory> [file2, ...]
 *
 * If a parameter is a directory, the template files within will be parsed.
 *
 * @version   $Id$
 * @link      http://js-gettext.sf.net/
 * @license   http://www.gnu.org/licenses/lgpl.html  GNU Lesser General Public License
 */

$root = $_SERVER['argv'][1];
array_splice($_SERVER['argv'], 1, 1);

// extensions of js files, used when going through a directory
$extensions = array('js');

// "fix" string - strip slashes, escape and convert new lines to \n
function fs($text) {
  $quote = $text[0];
  // Remove newlines
  $text = str_replace("\\\n", '', $text);
  // Unescape escaped quotes
  $text = str_replace('\\' . $quote, $quote, $text);
  // Remove end quotes
  $text = substr(ltrim($text, $quote), 0, -1);
  return $text;
}

// rips gettext strings from $file and prints them in C format
function do_file($file) {
    $content = @file_get_contents($file);
    $strings = array();

    if (empty($content)) {
      return;
    }

    global $root;

    // Match all calls to ts()
    // Note: \s also matches newlines with the 's' modifier.
    preg_match_all('~
      [^\w]ts\s*                                    # match "ts" with whitespace
      \(\s*                                         # match "(" argument list start
      ((?:(?:\'(?:\\\\\'|[^\'])*\'|"(?:\\\\"|[^"])*")(?:\s*\+\s*)?)+)\s*
      [,\)]                                         # match ")" or "," to finish
      ~sx', $content, $matches);
    foreach($matches[1] as $text) {
      print '#: ' . substr($file, strlen($root) + 1) . "\n";
      print 'msgid "' . fs($text) . "\"\n";
      print "msgstr \"\"\n\n";
    }
}

// go through a directory
function do_dir($dir)
{
    $d = dir($dir);

    while (false !== ($entry = $d->read())) {
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        $entry = $dir.'/'.$entry;

        if (is_dir($entry)) {
            // if a directory, go through it
            do_dir($entry);
        } else {
            // if file, parse only if extension is matched
            $pi = pathinfo($entry);

            if (isset($pi['extension']) && in_array($pi['extension'], $GLOBALS['extensions'])) {
                do_file($entry);
            }
        }
    }

    $d->close();
}

for ($ac=1; $ac < $_SERVER['argc']; $ac++) {
    if (is_dir($_SERVER['argv'][$ac])) {
        do_dir($_SERVER['argv'][$ac]);
    } else {
        do_file($_SERVER['argv'][$ac]);
    }
}
