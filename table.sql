-- phpMyAdmin SQL Dump
-- version 4.0.8
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Окт 27 2014 г., 10:34
-- Версия сервера: 5.6.19-log
-- Версия PHP: 5.5.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- База данных: `test`
--

-- --------------------------------------------------------

--
-- Структура таблицы `user_to_login`
--

CREATE TABLE IF NOT EXISTS `user_to_login` (
  `user_id` varchar(32) NOT NULL,
  `login` varchar(50) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`user_id`,`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
