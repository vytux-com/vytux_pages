# vytux_pages

Create additional resource pages for your [webtrees](https://www.webtrees.net) site.

Ported from the `simpl_pages` module by Nigel Osborne (kiwtrees.net), after its original author chose not to support webtrees 1.5. Ported to webtrees by Vytautas Krivickas ([vytux.com](https://vytux.com)).

> **Compatibility:** this module is for **webtrees 1.x** (1.6.0 or later). It does not work with webtrees 2.x.

Released: October 2014

## Contents

- [Description](#description)
- [Requirements](#requirements)
- [Installation](#installation)
- [Setup](#setup)
- [Upgrading](#upgrading)
- [Support](#support)

## Description

Adds a menu and a set of tabs containing pages of your own content (history, links, notes, anything you can write in HTML) to further enhance your webtrees site. Page content is stored in the webtrees database.

## Requirements

- webtrees 1.6.0 or later

## Installation

1. Unzip the files and upload them to the webtrees `modules_vX/` folder on your web server (`X` is `3` at the time of writing, but may change in future releases).
2. Sign in to webtrees as an administrator, go to **Administration → Modules** and find **Vytux_pages**. Make sure it is enabled.
3. Go to the **Menus** tab. Set the access level and position of the menu item. It will probably default to *Show only to managers*.
4. Click **Save**.

Refresh the Administration page and a new **Vytux_pages** item appears near the bottom of the left column. You use it during setup.

## Setup

Creating pages is much the same as creating FAQs; this module borrows much of the same code. Go to **Administration → Vytux_pages** and click **Add pages**.

1. Give each page a **title**. It is used as both the menu item and the tab label, so keep it short.
2. Enter the page **content**. You can use any formatting, images and links you like.
3. Set the **access level** to choose which user groups can see the page.
4. Choose the **languages** to show the page in. Select none to show it in all languages. Select single languages only if you also plan to offer translated versions.
5. Set the **page position**. This decides where the page appears in both the menu and the tabs, and can be changed later by drag and drop on the Vytux_pages administration screen. The first page is the default when the main menu item is clicked.
6. Use **page visibility** to show the page in all family trees, or only the current one.
7. Click **Save**.

### Optional changes

- **Overall pages title:** defaults to "Resource pages". Change it at row 319 of `vytux_pages/module.php`.
- **Menu item title:** taken from the module title, "Resources" by default. Change it at row 39 of `vytux_pages/module.php`.
- **Menu icons:** each theme has its own icons in the module's `themes/<theme>/` folder (`webtrees`, `clouds`, `colors`, `xenea`, ...). Replace an icon with your own of a suitable size, keeping the same file name.
- **Fonts and colours:** edit `themes/<theme>/style.css` in the module folder.
- **Overall heading:** add a heading (basic HTML allowed) between the apostrophes at row 316 of `vytux_pages/module.php`.

Changes to module files are overwritten when you upgrade, so keep notes of them.

## Upgrading

The module cannot import pages from `simpl_pages` automatically. Delete the old `simpl_pages` module, then either re-create your pages or, **after** enabling the new module, run this SQL on your database:

```sql
UPDATE `wt_block` SET `module_name`='vytux_pages' WHERE `module_name`='simpl_pages';
```

(Adjust the `wt_` table prefix if you use a different one.)

## Support

- **Email:** [webtrees@vytux.com](mailto:webtrees@vytux.com) for vytux add-on support.
- **Forum:** general webtrees support is available on the [webtrees forum](https://www.webtrees.net/index.php/en/forum/index-en).

## Copyright

© 2013-2014 Vytautas Krivickas and vytux.com
