# Formloader Package &amp; Module

Formloader is a Package &amp; Module combination which intends to centralize all form validation &amp; generation to a single location, providing a simple UI for managing forms.

### Note:

The version number matches with the FuelPHP install it requires, however it is an early alpha release and has many features which are planned or in progress. The main public methods should generally remain the same for the 1.x branch.

---

## Introduction

When writing CRUD apps, much of the logic and views are built around handling/rendering user input. Most of this is very repetitive and scattered throughout an application. This package/module combination aims to simplify the form creation process, storing the structure for forms in a consistent fashion in the filesystem and providing a simple user interface for managing the form logic, attributes, and views.

## Installation

Clone or download this repository into your "packages" directory

Once you have downloaded the Formloader package, all you need to do is enable it in your config.

	```
	'packages' => array(
		'formloader',
	),
	```

After that, navigate to `http://example.com/formloader` and the installer prompts should guide you through the rest

By default, the Formloader module will only be enabled when `Fuel::$env === "development"`. You can modify this in the configuration, but do so at your own risk... you don't want a public facing interface to be able to edit forms in production mode without any security measures.

## Class Methods
	
Please go <a href="http://formloader.tgriesser.com/docs">here</a> to find the current documentation for the formloader package

## Examples

 * <a href="http://formloader.tgriesser.dev/screencast/install">Installing/Overview</a>
 * <a href="http://formloader.tgriesser.dev/screencast/blog">Building a Blog</a>
 * <a href="http://formloader.tgriesser.dev/screencast/ecommerce">Building an Ecommerce Site</a>

---

## License:

Formloader is released under the MIT license
