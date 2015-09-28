-- phpMyAdmin SQL Dump
-- version 4.2.10
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Сен 28 2015 г., 12:05
-- Версия сервера: 5.6.21
-- Версия PHP: 5.5.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- База данных: `yii2_datastructure`
--

-- --------------------------------------------------------

--
-- Структура таблицы `migration`
--

DROP TABLE IF EXISTS `migration`;
CREATE TABLE IF NOT EXISTS `migration` (
  `version` varchar(180) NOT NULL,
  `apply_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `property`
--

DROP TABLE IF EXISTS `property`;
CREATE TABLE IF NOT EXISTS `property` (
`id` int(11) NOT NULL,
  `key` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `is_numeric` tinyint(1) DEFAULT '0',
  `is_internal` tinyint(1) DEFAULT '0',
  `allow_multiple_values` tinyint(1) DEFAULT '0',
  `storage_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `property_group`
--

DROP TABLE IF EXISTS `property_group`;
CREATE TABLE IF NOT EXISTS `property_group` (
`id` int(11) NOT NULL,
  `internal_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `property_group_translation`
--

DROP TABLE IF EXISTS `property_group_translation`;
CREATE TABLE IF NOT EXISTS `property_group_translation` (
  `model_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `property_property_group`
--

DROP TABLE IF EXISTS `property_property_group`;
CREATE TABLE IF NOT EXISTS `property_property_group` (
  `property_id` int(11) NOT NULL,
  `property_group_id` int(11) NOT NULL,
  `sort_order_property_groups` int(11) NOT NULL DEFAULT '0',
  `sort_order_group_properties` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `property_storage`
--

DROP TABLE IF EXISTS `property_storage`;
CREATE TABLE IF NOT EXISTS `property_storage` (
`id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `class_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `property_translation`
--

DROP TABLE IF EXISTS `property_translation`;
CREATE TABLE IF NOT EXISTS `property_translation` (
  `model_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `static_value`
--

DROP TABLE IF EXISTS `static_value`;
CREATE TABLE IF NOT EXISTS `static_value` (
`id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `static_value_translation`
--

DROP TABLE IF EXISTS `static_value_translation`;
CREATE TABLE IF NOT EXISTS `static_value_translation` (
  `model_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(80) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `migration`
--
ALTER TABLE `migration`
 ADD PRIMARY KEY (`version`);

--
-- Индексы таблицы `property`
--
ALTER TABLE `property`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `property_group`
--
ALTER TABLE `property_group`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `property_group_translation`
--
ALTER TABLE `property_group_translation`
 ADD PRIMARY KEY (`model_id`,`language_id`);

--
-- Индексы таблицы `property_property_group`
--
ALTER TABLE `property_property_group`
 ADD PRIMARY KEY (`property_id`,`property_group_id`);

--
-- Индексы таблицы `property_storage`
--
ALTER TABLE `property_storage`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `property_translation`
--
ALTER TABLE `property_translation`
 ADD PRIMARY KEY (`model_id`,`language_id`);

--
-- Индексы таблицы `static_value`
--
ALTER TABLE `static_value`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `static_value_translation`
--
ALTER TABLE `static_value_translation`
 ADD PRIMARY KEY (`model_id`,`language_id`), ADD KEY `slug` (`slug`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `property`
--
ALTER TABLE `property`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `property_group`
--
ALTER TABLE `property_group`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `property_storage`
--
ALTER TABLE `property_storage`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `static_value`
--
ALTER TABLE `static_value`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;