# FreeCRM — Verama job crawler
# @project FreeCRM
# @author bmankowski@gmail.com

from __future__ import annotations

import unittest

from verama.description import parse_description


class DescriptionParserTests(unittest.TestCase):
    def test_sections_from_headings(self) -> None:
        html = """
        <html><body>
        <h2>About the role</h2>
        <p>We need a specialist.</p>
        <h2>Requirements</h2>
        <ul><li>Linux</li><li>Python</li></ul>
        <h2>We offer</h2>
        <p>Remote work</p>
        </body></html>
        """
        result = parse_description(html)
        self.assertIn("specialist", result["description_text"].lower())
        sections = result["description_sections"]
        self.assertIn("about", sections)
        self.assertIn("requirements", sections)
        self.assertIn("offer", sections)
        self.assertIn("Linux", sections["requirements"])
        self.assertIn("Remote", sections["offer"])

    def test_no_headings_goes_to_other(self) -> None:
        html = "<p>Only a short blurb without structure.</p>"
        result = parse_description(html)
        self.assertEqual(set(result["description_sections"].keys()), {"other"})
        self.assertIn("blurb", result["description_sections"]["other"])

    def test_empty(self) -> None:
        result = parse_description("")
        self.assertEqual(result["description_text"], "")
        self.assertEqual(result["description_sections"], {})


if __name__ == "__main__":
    unittest.main()
