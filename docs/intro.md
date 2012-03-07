# Formloader Package &amp; Module

Formloader is a Package &amp; Module combination which intends to abstract all form validation &amp; generation to a single location, providing a simple UI for managing forms.

---

## Introduction

When writing CRUD apps, much of the logic and views are built around handling/rendering user input. Most of this is very repetitive and scattered throughout an application. This package/module combination aims to simplify the form creation process, storing the structure for forms in a consistent fashion in the filesystem (or MongoDB database - under construction) and providing a simple user interface for managing the form logic, attributes, and views.

## Installation

The Formloader class requires the Loopforge package, as well as the Parser package (included with Fuel by default). Please download these and add them to your `PKGPATH`. Once added, the Formloader package will add these packages automatically in the bootstrap.php

Once you have downloaded the Formloader package, all you need to do is enable it in your config.

	```
	'packages' => array(
		'formloader',
	),
	```

By default, the Formloader module will only be enabled when `Fuel::$env === "development"`. You can modify this in the configuration, but do so at your own risk... you don't want a public facing interface to be able to edit forms in production mode without any security measures.

---

## Disclaimer: 

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT OF THIRD PARTY RIGHTS. IN
NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE
OR OTHER DEALINGS IN THE SOFTWARE.