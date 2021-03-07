# Release Notes for Webshipper for Craft Commerce

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## 1.0.9 - 2021-03-07

### Added

* Added `EVENT_BEFORE_CREATE_ORDER_ITEMS` to `createOrder` function in `helpers\Connector` to make it possible to override what orderlines gets sent to Webshipper
* Added new webhook for `order/delete` event in Webshipper, to allow an order to be deleted from either the webshop or Webshipper.

### Changed

* Updated ordersync to now include original shipping from the webshop
* `helpers\Connector` now extends `yii\base\Component`
* Adjusted error loggin in `SyncOrder` job



### Fixed

* Fixed error where Sync-jobs always was being readded to the queue, even if they were succesfull
* Fixed twig filtering error on shipments tempate for the order page

## 1.0.8 - 2021-03-05

### Changed

* Updated error logging

### Fixed

* Fixed unit_price not subtracting VAT amount on lineitems

## 1.0.7 - 2021-02-25

### Changed

* Added new helper class `QD\commerce\webshipper\helpers\Log` which logs errors to a webshipper.log file
* `SyncOrder` updated to handle scenarios where the order was created in webshipper, but the id wasn't stored in Craft
* Added error loggers

## 1.0.6 - 2021-02-23

### Fixed

* Fixed error in `createOrder` function in`QD\commerce\webshipper\helpers\Conector` where orderItem unitprice was based on snapshot data instead of lineitem data. This meant that special prices and discounts was ignored when sending to webshipper

## 1.0.5 - 2021-02-19

### Changed

* Added check for the ENV variable `WEBSHIPPER_USESTAGING`. If set to 0, webshipper will use staging API instead of production API.

## 1.0.2 - 2020-11-17

* Fixed error where `getWebshipperRateId` whould throw a notice when no rows was found

## 1.0.1 - 2020-11-12

### Fixed

* Fixed class loding error for Connector class due to error in filename

## 1.0.0 - 2020-11-10

Initial release of the Webshipper plugin to the Craft Store

