# ZPanelCP

Version: 11.0.0

## Description

ZPanel is an open-source web hosting control panel written in PHP and is compatible
with Microsoft Windows and POSIX (Linux, UNIX, MacOSX and the BSD's).

## License agreement

ZPanel is licensed under the GNU GENERAL PUBLIC LICENSE (GPL v3) you can view a copy of this license either by opening the LICENSE file in the root of this folder or by visiting:- http://www.gnu.org/copyleft/gpl.html

## Instalation and Usage

1. Set correct database credentials in "cnf/db.php"

2. Import into database correct file with .sql extension form "etc\build\config_packs" folder

3. Set up password for "zadmin" user via ``php setzadmin --set <password>`` command in "bin" folder

4. Set correct domain for password reset with following command
   ``UPDATE `x_settings`SET`so_value_tx`= 'yourdomain.com' WHERE`x_settings`.`so_name_vc` = 'zpanel_domain';``

