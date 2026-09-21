# -*- coding: utf-8 -*-
"""Write src/Resources/lang/<locale>/app.php from translations.json.

    venv/Scripts/python.exe plugins/bagisto-search/render_lang.py

The translations are kept as JSON so that a quote or a backslash in any of
twenty-one languages is escaped by code rather than by hand, and so that a
missing or empty string is a build error rather than a page that silently
falls back to English.

English is the source and is NOT rendered: en/app.php is written by hand and
is what the JSON is checked against.
"""
import io
import json
import os

HERE = os.path.dirname(os.path.abspath(__file__))
LANG = os.path.join(HERE, 'src', 'Resources', 'lang')
SOURCE = os.path.join(HERE, 'translations.json')

HEADER = ("<?php\n\n"
          "/**\n"
          " * Asyntai AI Search for Bagisto: %s strings.\n"
          " *\n"
          " * Generated from translations.json by render_lang.py. Edit the JSON.\n"
          " */\n\n")

# The shape of en/app.php, so every rendered file has the same keys in the
# same order and a reviewer can read two files side by side.
SHAPE = [
    ('menu', ['title']),
    (None, ['title']),
    ('hero', ['title', 'text', 'button', 'point_1', 'point_2', 'point_3']),
    ('headings', ['live', 'setting_up', 'blocked', 'unknown']),
    ('status', ['not_connected', 'live_pages', 'live_products', 'plan', 'limit',
                'reading', 'unknown_widget', 'unreachable', 'off']),
    (None, ['allowance', 'connected_as', 'dashboard', 'analytics', 'check_now',
            'disconnect']),
    ('preview', ['title', 'text', 'button']),
    ('settings', ['title', 'placement', 'placement_help', 'placement_replace',
                  'placement_manual', 'selector', 'selector_help', 'placeholder',
                  'placeholder_help', 'accent', 'accent_help', 'feed', 'feed_help',
                  'save', 'saved']),
    ('theme', ['title', 'text']),
    ('js', ['preparing', 'waiting', 'saving', 'blocked', 'open_link', 'failed',
            'timeout', 'signed_out', 'confirm', 'unreachable', 'expired']),
]

# Placeholders Laravel substitutes. A translation that loses one renders the
# literal ":count" to a merchant, so their presence is checked, not assumed.
PLACEHOLDERS = {
    'status.live_products': [':count'],
    'allowance': [':left', ':limit'],
    'connected_as': [':email'],
}


def flat_keys():
    for group, names in SHAPE:
        for name in names:
            yield ('%s.%s' % (group, name)) if group else name


def php_quote(value):
    return "'" + value.replace('\\', '\\\\').replace("'", "\\'") + "'"


def render(locale, strings, language_name):
    lines = [HEADER % language_name, "return [\n    'admin' => [\n"]

    for group, names in SHAPE:
        if group:
            lines.append("        '%s' => [\n" % group)
            pad = ' ' * 12
        else:
            pad = ' ' * 8
        width = max(len(n) for n in names)
        for name in names:
            key = ('%s.%s' % (group, name)) if group else name
            lines.append("%s'%s'%s => %s,\n"
                         % (pad, name, ' ' * (width - len(name)), php_quote(strings[key])))
        if group:
            lines.append("        ],\n")
        lines.append("\n")

    lines.append("    ],\n];\n")
    return ''.join(lines)


def main():
    data = json.load(io.open(SOURCE, encoding='utf-8'))
    names = data['languages']
    expected = list(flat_keys())
    written = 0

    for locale, strings in sorted(data['translations'].items()):
        missing = [k for k in expected if not (strings.get(k) or '').strip()]
        if missing:
            raise SystemExit('%s is missing %d strings, first: %s'
                             % (locale, len(missing), missing[0]))
        extra = [k for k in strings if k not in expected]
        if extra:
            raise SystemExit('%s has keys English does not: %s' % (locale, extra))
        for key, marks in PLACEHOLDERS.items():
            for mark in marks:
                if mark not in strings[key]:
                    raise SystemExit('%s lost %s from %s' % (locale, mark, key))

        folder = os.path.join(LANG, locale)
        if not os.path.isdir(folder):
            os.makedirs(folder)
        io.open(os.path.join(folder, 'app.php'), 'w', encoding='utf-8', newline='\n').write(
            render(locale, strings, names.get(locale, locale)))
        written += 1
        print('wrote %-6s %s' % (locale, names.get(locale, '')))

    print('%d locales written, %d strings each' % (written, len(expected)))


if __name__ == '__main__':
    main()
