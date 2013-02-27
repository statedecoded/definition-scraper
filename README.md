# Definition Scraper

Extracts defined terms from strings of text. Contracts, laws, terms of service, etc. all contain terms that are essential to understanding the text. This locates those defined terms and extracts those terms and their definitions.


# Instructions
Take the text and break it up into paragraphs, storing the text as an object, with each paragraph as a numbered property. Create a new instance of `DefinitionScraper` (`$parser = new DefinitionScraper()`), store the text object as the `text` property, and then invoke the method `parse()`. That will create a member object property, `definitions`, that contains a list of terms and definitions, with each term serving as the property’s key.

When no definitions can be found, it returns false.

Definitions must be contained within quotation marks, either straight (aka "double primes," Unicode U+2033) or directional (aka "smart quotes.")

To link terms and their definitions, it looks for any of the following phrases between the two:

* mean
* means
* shall include
* includes
* has the same meaning as
* shall be construed
* shall also be construed to mean

Additional linking phrases can be added to the `$linking_phrases` array, found at the beginning of the method.


# Example

For example, this text, from the GNU Public License:

> 0. Definitions.
> “This License” refers to version 3 of the GNU General Public License.
>
> “Copyright” also means copyright-like laws that apply to other kinds of works, such as semiconductor masks.
> 
> “The Program” refers to any copyrightable work licensed under this License. Each licensee is addressed as “you”. “Licensees” and “recipients” may be individuals or organizations.
> 
> To “modify” a work means to copy from or adapt all or part of the work in a fashion requiring copyright permission, other than the making of an exact copy. The resulting work is called a “modified version” of the earlier work or a work “based on” the earlier work.
> 
> A “covered work” means either the unmodified Program or a work based on the Program.
> 
> To “propagate” a work means to do anything with it that, without permission, would make you directly or secondarily liable for infringement under applicable copyright law, except executing it on a computer or modifying a private copy. Propagation includes copying, distribution (with or without modification), making available to the public, and in some countries other activities as well.
> 
> To “convey” a work means any kind of propagation that enables other parties to make or receive copies. Mere interaction with a user through a computer network, with no transfer of a copy, is not conveying.
> 
> An interactive user interface displays “Appropriate Legal Notices” to the extent that it includes a convenient and prominently visible feature that (1) displays an appropriate copyright notice, and (2) tells the user that there is no warranty for the work (except to the extent that warranties are provided), that licensees may convey the work under this License, and how to view a copy of this License. If the interface presents a list of user commands or options, such as a menu, a prominent item in the list meets this criterion.

To improve this text with this tool, first break it up into its component paragraphs and store it as an object (`$text = (object) explode(PHP_EOL, $text);`). Then feed that text into Definition Scraper:

```php
$parser = new DefinitionScraper();
$parser->text = $text;
$parser->parse();
```

This returns the following as `$parser->definitions`:

```
(
	[copyright] => “Copyright” also means copyright-like laws that apply to other kinds of works, such as semiconductor masks.
	[modify] => “modify” a work means to copy from or adapt all or part of the work in a fashion requiring copyright permission, other than the making of an exact copy. The resulting work is called a “modified version” of the earlier work or a work “based on” the earlier work.
	[modified version] => “modify” a work means to copy from or adapt all or part of the work in a fashion requiring copyright permission, other than the making of an exact copy. The resulting work is called a “modified version” of the earlier work or a work “based on” the earlier work.
	[based on] => “modify” a work means to copy from or adapt all or part of the work in a fashion requiring copyright permission, other than the making of an exact copy. The resulting work is called a “modified version” of the earlier work or a work “based on” the earlier work.
	[covered work] => “covered work” means either the unmodified Program or a work based on the Program.
	[propagate] => “propagate” a work means to do anything with it that, without permission, would make you directly or secondarily liable for infringement under applicable copyright law, except executing it on a computer or modifying a private copy. Propagation includes copying, distribution (with or without modification), making available to the public, and in some countries other activities as well.
	[convey] => “convey” a work means any kind of propagation that enables other parties to make or receive copies. Mere interaction with a user through a computer network, with no transfer of a copy, is not conveying.
	[appropriate legal notices] => “Appropriate Legal Notices” to the extent that it includes a convenient and prominently visible feature that (1) displays an appropriate copyright notice, and (2) tells the user that there is no warranty for the work (except to the extent that warranties are provided), that licensees may convey the work under this License, and how to view a copy of this License. If the interface presents a list of user commands or options, such as a menu, a prominent item in the list meets this criterion.
)
```

# Shortcomings


## Quotation marks requirement
This requires that each defined term be contained within quotation marks. While this is a very common practice, it is not universal. It would be straightforward to modify Definition Scraper to support the extraction of terms that are contained within something other than quotation marks (e.g., `<em></em>` tags), but extracting terms that are in no way demarcated would be somebody more difficult.


## Inconsistent text formatting

As can be seen in the above example, definitions do not always appears in a dictionary-style layout. Sometimes terms appear in the midst of a sentence, leading to grammatically awkward definitions (e.g. "‘Appropriate Legal Notices’ to the extent that it includes a convenient and prominently visible feature...”) that might be understandable, but are still non-ideal.

## Too-broad definitions

As can also be seen the above example, the terms "modified version" and "based on" are defined, with definitions that are duplicates of "modified." This is intentional, because all three terms appear within the same definition, so the parser cannot tell where one definition ends and the next one begins. More awkwardly, the term "based on" is never actually defined, despite being in quotes. (One might argue that this is a bug in the text of the GPL.)
