# OpenCart Module Template Generator

Create a basic template for Opencart module using Robo

## Installation

```bash

#Clone the repository
$ git clone https://github.com/nursanamar/OpenCart-module-generator.git
$ cd OpenCart-module-generator

#Install dependecies
$ composer install

#.env file
$ cp .env.example .env

```

Setup your `.env` file

## Usage

### Create new module

```bash
$ vendor/bin/robo module:new
```
Generate controller,model,view,etc files in `src`

### Install module

```bash
$ vendor/bin/robo module:install
```
Copy all file form `src/upload` into your Opencart directory (from your .env file)

### Watch module

```bash
$ vendor/bin/robo module:watch
```

Wacth any changes in `src/upload` and copy changed files into Opencart directory (from your .env file)

### Build module

```bash
$ vendor/bin/robo module:build
```

Generate ocmod file in `build` folder, add `--with-obf` option to build the obfuscated version of your ocmod