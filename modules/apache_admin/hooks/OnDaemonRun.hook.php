<?php

echo fs_filehandler::NewLine() . "START Apache Config Hook." . fs_filehandler::NewLine();
if ( ui_module::CheckModuleEnabled( 'Apache Config' ) ) {
    echo "Apache Admin module ENABLED..." . fs_filehandler::NewLine();
    TriggerApacheQuotaUsage();
    if ( ctrl_options::GetSystemOption( 'apache_changed' ) == strtolower( "true" ) ) {
        echo "Apache Config has changed..." . fs_filehandler::NewLine();
        if ( ctrl_options::GetSystemOption( 'apache_backup' ) == strtolower( "true" ) ) {
            echo "Backing up Apache Config to: " . ctrl_options::GetSystemOption( 'apache_budir' ) . fs_filehandler::NewLine();
            BackupVhostConfigFile();
        }
        echo "Begin writing Apache Config to: " . ctrl_options::GetSystemOption( 'apache_vhost' ) . fs_filehandler::NewLine();
        WriteVhostConfigFile();
    }
    else {
        echo "Apache Config has NOT changed...nothing to do." . fs_filehandler::NewLine();
    }
}
else {
    echo "Apache Admin module DISABLED...nothing to do." . fs_filehandler::NewLine();
}
echo "END Apache Config Hook." . fs_filehandler::NewLine();

/**
 *
 * @param string $vhostName
 * @param numeric $customPort
 * @param string $userEmail
 * @return string
 *
 */
function BuildVhostPortForward( $vhostName, $customPort, $userEmail )
{
    $line = fs_filehandler::NewLine() . fs_filehandler::NewLine();
    $line .= "# DOMAIN: " . $vhostName . fs_filehandler::NewLine();
    $line .= "# PORT FORWARD FROM 80 TO: " . $customPort . fs_filehandler::NewLine();
    $line .= "<virtualhost *:80>" . fs_filehandler::NewLine();
    $line .= "ServerName " . $vhostName . fs_filehandler::NewLine();
    $line .= "ServerAlias " . $vhostName . " www." . $vhostName . fs_filehandler::NewLine();
    $line .= "ServerAdmin " . $userEmail . fs_filehandler::NewLine();
    $line .= "RewriteEngine on" . fs_filehandler::NewLine();
    $line .= "ReWriteCond %{SERVER_PORT} !^" . $customPort . "$" . fs_filehandler::NewLine();
    if ( $customPort === "443" ) {
        $line .= "RewriteRule ^/(.*) https://%{HTTP_HOST}/$1 [NC,R,L] " . fs_filehandler::NewLine();
    }
    else {
        $line .= "RewriteRule ^/(.*) http://%{HTTP_HOST}:" . $customPort . "/$1 [NC,R,L] " . fs_filehandler::NewLine();
    }
    $line .= "</virtualhost>" . fs_filehandler::NewLine();
    $line .= "# END DOMAIN: " . $vhostName . fs_filehandler::NewLine() . fs_filehandler::NewLine();

    return $line;
}

function WriteVhostConfigFile()
{
    global $zdbh;

    //Get email for server admin of zpanel
    $getserveremail = $zdbh->query( "SELECT ac_email_vc FROM x_accounts where ac_id_pk=1" )->fetch();
    if ( $getserveremail[ 'ac_email_vc' ] != "" ) {
        $serveremail = $getserveremail[ 'ac_email_vc' ];
    }
    else {
        $serveremail = "postmaster@" . ctrl_options::GetSystemOption( 'zpanel_domain' );
    }

    $customPorts = array( );
    $portQuery  = $zdbh->prepare( "SELECT vh_custom_port_in, vh_deleted_ts FROM zpanel_core.x_vhosts WHERE vh_custom_port_in IS NOT NULL AND vh_deleted_ts IS NULL" );
    $portQuery->execute();
    while ( $rowport    = $portQuery->fetch() ) {
        $customPorts[ ] = $rowport[ 'vh_custom_port_in' ];
    }
    $customPortList = array_unique($customPorts);

    /*
     * ##############################################################################################################
     * #
     * # Default Virtual Host Container
     * #
     * ##############################################################################################################
     */

    $line = "################################################################" . fs_filehandler::NewLine();
    $line .= "# Apache VHOST configuration file" . fs_filehandler::NewLine();
    $line .= "# Automatically generated by ZPanel " . sys_versions::ShowZpanelVersion() . fs_filehandler::NewLine();
    $line .= "# Generated on: " . date( ctrl_options::GetSystemOption( 'zpanel_df' ), time() ) . fs_filehandler::NewLine();
    $line .= "################################################################" . fs_filehandler::NewLine();
    $line .= "" . fs_filehandler::NewLine();

    // ZPanel default virtual host container
    $line .= "NameVirtualHost *:" . ctrl_options::GetSystemOption( 'apache_port' ) . "" . fs_filehandler::NewLine();
    foreach ( $customPortList as $port ) {
        $line .= "NameVirtualHost *:" . $port . "" . fs_filehandler::NewLine();
    }
    $line .= "" . fs_filehandler::NewLine();
    $line .= "# Configuration for ZPanel control panel." . fs_filehandler::NewLine();
    $line .= "<VirtualHost *:" . ctrl_options::GetSystemOption( 'apache_port' ) . ">" . fs_filehandler::NewLine();
    $line .= "ServerAdmin " . $serveremail . fs_filehandler::NewLine();
    $line .= "DocumentRoot \"" . ctrl_options::GetSystemOption( 'zpanel_root' ) . "\"" . fs_filehandler::NewLine();
    $line .= "ServerName " . ctrl_options::GetSystemOption( 'zpanel_domain' ) . "" . fs_filehandler::NewLine();
    // disable *.zpaneldomain as Zpanel host is already default
    // $line .= "ServerAlias *." . ctrl_options::GetSystemOption( 'zpanel_domain' ) . "" . fs_filehandler::NewLine();
    $line .= "AddType application/x-httpd-php .php" . fs_filehandler::NewLine();
    $line .= "<Directory \"" . ctrl_options::GetSystemOption( 'zpanel_root' ) . "\">" . fs_filehandler::NewLine();
    $line .= "Options FollowSymLinks" . fs_filehandler::NewLine();
    $line .= "	AllowOverride All" . fs_filehandler::NewLine();
    $line .= "	Require all granted" . fs_filehandler::NewLine();
    $line .= "</Directory>" . fs_filehandler::NewLine();
    $line .= "" . fs_filehandler::NewLine();
    $line .= "# Custom settings are loaded below this line (if any exist)" . fs_filehandler::NewLine();

    // Global custom zpanel entry
    $line .= ctrl_options::GetSystemOption( 'global_zpcustom' ) . fs_filehandler::NewLine();

    $line .= "</VirtualHost>" . fs_filehandler::NewLine();

    $line .= "" . fs_filehandler::NewLine();
    $line .= "################################################################" . fs_filehandler::NewLine();
    $line .= "# ZPanel generated VHOST configurations below....." . fs_filehandler::NewLine();
    $line .= "################################################################" . fs_filehandler::NewLine();
    $line .= "" . fs_filehandler::NewLine();

    /*
     * ##############################################################################################################
     * #
     * # All Virtual Host Containers
     * #
     * ##############################################################################################################
     */

    // Zpanel virtual host container configuration
    $sql      = $zdbh->prepare( "SELECT * FROM x_vhosts WHERE vh_deleted_ts IS NULL" );
    $sql->execute();
    while ( $rowvhost = $sql->fetch() ) {

        // Grab some variables we will use for later...
        $vhostuser = ctrl_users::GetUserDetail( $rowvhost[ 'vh_acc_fk' ] );
        $bandwidth = ctrl_users::GetQuotaUsages( 'bandwidth', $vhostuser[ 'userid' ] );
        $diskspace = ctrl_users::GetQuotaUsages( 'diskspace', $vhostuser[ 'userid' ] );
        // Set the vhosts to "LIVE"
        $vsql      = $zdbh->prepare( "UPDATE x_vhosts SET vh_active_in=1 WHERE vh_id_pk=:id" );
        $vsql->bindParam( ':id', $rowvhost[ 'vh_id_pk' ] );
        $vsql->execute();
        // Add a default email if no email found for client.
        if ( fs_director::CheckForEmptyValue( $vhostuser[ 'email' ] ) ) {
            $useremail = "postmaster@" . $rowvhost[ 'vh_name_vc' ];
        }
        else {
            $useremail = $vhostuser[ 'email' ];
        }
        // Check if domain or subdomain to see if we add an alias with 'www'
        if ( $rowvhost[ 'vh_type_in' ] == 2 ) {
            $serveralias = $rowvhost[ 'vh_name_vc' ];
        }
        else {
            $serveralias = $rowvhost[ 'vh_name_vc' ] . " www." . $rowvhost[ 'vh_name_vc' ];
        }

        if ( fs_director::CheckForEmptyValue( $rowvhost[ 'vh_custom_port_in' ] ) ) {
            $vhostPort = ctrl_options::GetSystemOption( 'apache_port' );
        }
        else {
            $vhostPort = $rowvhost[ 'vh_custom_port_in' ];
        }

        if ( fs_director::CheckForEmptyValue( $rowvhost[ 'vh_custom_ip_vc' ] ) ) {
            $vhostIp = "*";
        }
        else {
            $vhostIp = $rowvhost[ 'vh_custom_ip_vc' ];
        }




        //Domain is enabled
        //Line1: Domain enabled - Client also is enabled.
        //Line2: Domain enabled - Client may be disabled, but 'Allow Disabled' = 'true' in apache settings.
        if ( $rowvhost[ 'vh_enabled_in' ] == 1 && ctrl_users::CheckUserEnabled( $rowvhost[ 'vh_acc_fk' ] ) ||
            $rowvhost[ 'vh_enabled_in' ] == 1 && ctrl_options::GetSystemOption( 'apache_allow_disabled' ) == strtolower( "true" ) ) {

            /*
             * ##################################################
             * #
             * # Disk Quotas Check
             * #
             * ##################################################
             */

            //Domain is beyond its diskusage
            if ( $vhostuser[ 'diskquota' ] != 0 && $diskspace > $vhostuser[ 'diskquota' ] ) {
                $line .= "# DOMAIN: " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "# THIS DOMAIN HAS BEEN DISABLED FOR QUOTA OVERAGE" . fs_filehandler::NewLine();
                $line .= "<virtualhost " . $vhostIp . ":" . $vhostPort . ">" . fs_filehandler::NewLine();
                $line .= "ServerName " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "ServerAlias " . $rowvhost[ 'vh_name_vc' ] . " www." . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "ServerAdmin " . $useremail . fs_filehandler::NewLine();
                $line .= "DocumentRoot \"" . ctrl_options::GetSystemOption( 'static_dir' ) . "diskexceeded\"" . fs_filehandler::NewLine();
                $line .= "<Directory />" . fs_filehandler::NewLine();
                $line .= "Options FollowSymLinks Indexes" . fs_filehandler::NewLine();
                $line .= "AllowOverride All" . fs_filehandler::NewLine();
                $line .= "Require all granted" . fs_filehandler::NewLine();
                $line .= "</Directory>" . fs_filehandler::NewLine();
                $line .= ctrl_options::GetSystemOption( 'php_handler' ) . fs_filehandler::NewLine();
                $line .= ctrl_options::GetSystemOption( 'dir_index' ) . fs_filehandler::NewLine();
                $line .= "</virtualhost>" . fs_filehandler::NewLine();
                $line .= "# END DOMAIN: " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "################################################################" . fs_filehandler::NewLine();
                $line .= fs_filehandler::NewLine();
                if ( $rowvhost[ 'vh_portforward_in' ] <> 0 ) {
                    $line .= BuildVhostPortForward( $rowvhost[ 'vh_name_vc' ], $vhostPort, $useremail );
                }
                $line .= fs_filehandler::NewLine();
                /*
                 * ##################################################
                 * #
                 * # Bandwidth Quotas Check
                 * #
                 * ##################################################
                 */

                //Domain is beyond its quota
            }
            elseif ( $vhostuser[ 'bandwidthquota' ] != 0 && $bandwidth > $vhostuser[ 'bandwidthquota' ] ) {
                $line .= "# DOMAIN: " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "# THIS DOMAIN HAS BEEN DISABLED FOR BANDWIDTH OVERAGE" . fs_filehandler::NewLine();
                $line .= "<virtualhost " . $vhostIp . ":" . $vhostPort . ">" . fs_filehandler::NewLine();
                $line .= "ServerName " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "ServerAlias " . $rowvhost[ 'vh_name_vc' ] . " www." . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "ServerAdmin " . $useremail . fs_filehandler::NewLine();
                $line .= "DocumentRoot \"" . ctrl_options::GetSystemOption( 'static_dir' ) . "bandwidthexceeded\"" . fs_filehandler::NewLine();
                $line .= "<Directory />" . fs_filehandler::NewLine();
                $line .= "Options FollowSymLinks Indexes" . fs_filehandler::NewLine();
                $line .= "AllowOverride All" . fs_filehandler::NewLine();
                $line .= "Require all granted" . fs_filehandler::NewLine();
                $line .= "</Directory>" . fs_filehandler::NewLine();
                $line .= ctrl_options::GetSystemOption( 'php_handler' ) . fs_filehandler::NewLine();
                $line .= ctrl_options::GetSystemOption( 'dir_index' ) . fs_filehandler::NewLine();
                $line .= "</virtualhost>" . fs_filehandler::NewLine();
                $line .= "# END DOMAIN: " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "################################################################" . fs_filehandler::NewLine();
                $line .= fs_filehandler::NewLine();
                if ( $rowvhost[ 'vh_portforward_in' ] <> 0 ) {
                    $line .= BuildVhostPortForward( $rowvhost[ 'vh_name_vc' ], $vhostPort, $useremail );
                }
                $line .= fs_filehandler::NewLine();
                /*
                 * ##################################################
                 * #
                 * # Parked Domain
                 * #
                 * ##################################################
                 */

                //Domain is a PARKED domain.
            }
            elseif ( $rowvhost[ 'vh_type_in' ] == 3 ) {
                $line .= "# DOMAIN: " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "<virtualhost " . $vhostIp . ":" . $vhostPort . ">" . fs_filehandler::NewLine();
                $line .= "ServerName " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "ServerAlias " . $rowvhost[ 'vh_name_vc' ] . " www." . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "ServerAdmin " . $useremail . fs_filehandler::NewLine();
                $line .= "DocumentRoot \"" . ctrl_options::GetSystemOption( 'parking_path' ) . "\"" . fs_filehandler::NewLine();
                $line .= "<Directory />" . fs_filehandler::NewLine();
                $line .= "Options FollowSymLinks Indexes" . fs_filehandler::NewLine();
                $line .= "AllowOverride All" . fs_filehandler::NewLine();
                $line .= "Require all granted" . fs_filehandler::NewLine();
                $line .= "</Directory>" . fs_filehandler::NewLine();
                $line .= ctrl_options::GetSystemOption( 'php_handler' ) . fs_filehandler::NewLine();
                $line .= ctrl_options::GetSystemOption( 'dir_index' ) . fs_filehandler::NewLine();
                $line .= "# Custom Global Settings (if any exist)" . fs_filehandler::NewLine();
                $line .= ctrl_options::GetSystemOption( 'global_vhcustom' ) . fs_filehandler::NewLine();
                $line .= "# Custom VH settings (if any exist)" . fs_filehandler::NewLine();
                $line .= $rowvhost[ 'vh_custom_tx' ] . fs_filehandler::NewLine();
                $line .= "</virtualhost>" . fs_filehandler::NewLine();
                $line .= "# END DOMAIN: " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "################################################################" . fs_filehandler::NewLine();
                $line .= fs_filehandler::NewLine();
                if ( $rowvhost[ 'vh_portforward_in' ] <> 0 ) {
                    $line .= BuildVhostPortForward( $rowvhost[ 'vh_name_vc' ], $vhostPort, $useremail );
                }
                $line .= fs_filehandler::NewLine();
                /*
                 * ##################################################
                 * #
                 * # Regular or Sub domain
                 * #
                 * ##################################################
                 */

                //Domain is a regular domain or a subdomain.
            }
            else {
                $line .= "# DOMAIN: " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "<virtualhost " . $vhostIp . ":" . $vhostPort . ">" . fs_filehandler::NewLine();

                /*
                 * todo
                 */
                // Bandwidth Settings
                //$line .= "Include C:/ZPanel/bin/apache/conf/mod_bw/mod_bw/mod_bw_Administration.conf" . fs_filehandler::NewLine();
                // Server name, alias, email settings
                $line .= "ServerName " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "ServerAlias " . $serveralias . fs_filehandler::NewLine();
                $line .= "ServerAdmin " . $useremail . fs_filehandler::NewLine();
                // Document root
                $line .= "DocumentRoot \"" . ctrl_options::GetSystemOption( 'hosted_dir' ) . $vhostuser[ 'username' ] . "/public_html" . $rowvhost[ 'vh_directory_vc' ] . "\"" . fs_filehandler::NewLine();
                // Get Package openbasedir and suhosin enabled options
                if ( ctrl_options::GetSystemOption( 'use_openbase' ) == "true" ) {
                    if ( $rowvhost[ 'vh_obasedir_in' ] <> 0 ) {
                        $line .= "php_admin_value open_basedir \"" . ctrl_options::GetSystemOption( 'hosted_dir' ) . $vhostuser[ 'username' ] . "/public_html" . $rowvhost[ 'vh_directory_vc' ] . ctrl_options::GetSystemOption( 'openbase_seperator' ) . ctrl_options::GetSystemOption( 'openbase_temp' ) . "\"" . fs_filehandler::NewLine();
                    }
                }
                if ( ctrl_options::GetSystemOption( 'use_suhosin' ) == "true" ) {
                    if ( $rowvhost[ 'vh_suhosin_in' ] <> 0 ) {
                        $line .= ctrl_options::GetSystemOption( 'suhosin_value' ) . fs_filehandler::NewLine();
                    }
                }
                // Logs
                if ( !is_dir( ctrl_options::GetSystemOption( 'log_dir' ) . "domains/" . $vhostuser[ 'username' ] . "/" ) ) {
                    fs_director::CreateDirectory( ctrl_options::GetSystemOption( 'log_dir' ) . "domains/" . $vhostuser[ 'username' ] . "/" );
                }
                $line .= "ErrorLog \"" . ctrl_options::GetSystemOption( 'log_dir' ) . "domains/" . $vhostuser[ 'username' ] . "/" . $rowvhost[ 'vh_name_vc' ] . "-error.log\" " . fs_filehandler::NewLine();
                $line .= "CustomLog \"" . ctrl_options::GetSystemOption( 'log_dir' ) . "domains/" . $vhostuser[ 'username' ] . "/" . $rowvhost[ 'vh_name_vc' ] . "-access.log\" " . ctrl_options::GetSystemOption( 'access_log_format' ) . fs_filehandler::NewLine();
                $line .= "CustomLog \"" . ctrl_options::GetSystemOption( 'log_dir' ) . "domains/" . $vhostuser[ 'username' ] . "/" . $rowvhost[ 'vh_name_vc' ] . "-bandwidth.log\" " . ctrl_options::GetSystemOption( 'bandwidth_log_format' ) . fs_filehandler::NewLine();

                // Directory options
                $line .= "<Directory />" . fs_filehandler::NewLine();
                $line .= "Options FollowSymLinks Indexes" . fs_filehandler::NewLine();
                $line .= "AllowOverride All" . fs_filehandler::NewLine();
                $line .= "Require all granted" . fs_filehandler::NewLine();
                $line .= "</Directory>" . fs_filehandler::NewLine();

                // Get Package php and cgi enabled options
                $rows        = $zdbh->prepare( "SELECT * FROM x_packages WHERE pk_id_pk=:packageid AND pk_deleted_ts IS NULL" );
                $rows->bindParam( ':packageid', $vhostuser[ 'packageid' ] );
                $rows->execute();
                $packageinfo = $rows->fetch();
                if ( $packageinfo[ 'pk_enablephp_in' ] <> 0 ) {
                    $line .= ctrl_options::GetSystemOption( 'php_handler' ) . fs_filehandler::NewLine();
                }
                if ( $packageinfo[ 'pk_enablecgi_in' ] <> 0 ) {
                    $line .= ctrl_options::GetSystemOption( 'cgi_handler' ) . fs_filehandler::NewLine();
                    if ( !is_dir( ctrl_options::GetSystemOption( 'hosted_dir' ) . $vhostuser[ 'username' ] . "/public_html" . $rowvhost[ 'vh_directory_vc' ] . "/_cgi-bin" ) ) {
                        fs_director::CreateDirectory( ctrl_options::GetSystemOption( 'hosted_dir' ) . $vhostuser[ 'username' ] . "/public_html" . $rowvhost[ 'vh_directory_vc' ] . "/_cgi-bin" );
                    }
                }

                // Error documents:- Error pages are added automatically if they are found in the _errorpages directory
                // and if they are a valid error code, and saved in the proper format, i.e. <error_number>.html
                $errorpages = ctrl_options::GetSystemOption( 'hosted_dir' ) . $vhostuser[ 'username' ] . "/public_html" . $rowvhost[ 'vh_directory_vc' ] . "/_errorpages";
                if ( is_dir( $errorpages ) ) {
                    if ( $handle = opendir( $errorpages ) ) {
                        while ( ($file = readdir( $handle )) !== false ) {
                            if ( $file != "." && $file != ".." ) {
                                $page = explode( ".", $file );
                                if ( !fs_director::CheckForEmptyValue( CheckErrorDocument( $page[ 0 ] ) ) ) {
                                    $line .= "ErrorDocument " . $page[ 0 ] . " /_errorpages/" . $page[ 0 ] . ".html" . fs_filehandler::NewLine();
                                }
                            }
                        }
                        closedir( $handle );
                    }
                }

                // Directory indexes
                $line .= ctrl_options::GetSystemOption( 'dir_index' ) . fs_filehandler::NewLine();

                // Global custom global vh entry
                $line .= "# Custom Global Settings (if any exist)" . fs_filehandler::NewLine();
                $line .= ctrl_options::GetSystemOption( 'global_vhcustom' ) . fs_filehandler::NewLine();

                // Client custom vh entry
                $line .= "# Custom VH settings (if any exist)" . fs_filehandler::NewLine();
                $line .= $rowvhost[ 'vh_custom_tx' ] . fs_filehandler::NewLine();

                // End Virtual Host Settings
                $line .= "</virtualhost>" . fs_filehandler::NewLine();
                $line .= "# END DOMAIN: " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
                $line .= "################################################################" . fs_filehandler::NewLine();
                $line .= fs_filehandler::NewLine();
                if ( $rowvhost[ 'vh_portforward_in' ] <> 0 ) {
                    $line .= BuildVhostPortForward( $rowvhost[ 'vh_name_vc' ], $vhostPort, $useremail );
                }
                $line .= fs_filehandler::NewLine();
            }

            /*
             * ##################################################
             * #
             * # Disabled domain
             * #
             * ##################################################
             */
        }
        else {
            //Domain is NOT enabled
            $line .= "# DOMAIN: " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
            $line .= "# THIS DOMAIN HAS BEEN DISABLED" . fs_filehandler::NewLine();
            $line .= "<virtualhost " . $vhostIp . ":" . $vhostPort . ">" . fs_filehandler::NewLine();
            $line .= "ServerName " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
            $line .= "ServerAlias " . $rowvhost[ 'vh_name_vc' ] . " www." . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
            $line .= "ServerAdmin " . $useremail . fs_filehandler::NewLine();
            $line .= "DocumentRoot \"" . ctrl_options::GetSystemOption( 'static_dir' ) . "disabled\"" . fs_filehandler::NewLine();
            $line .= "<Directory />" . fs_filehandler::NewLine();
            $line .= "Options FollowSymLinks Indexes" . fs_filehandler::NewLine();
            $line .= "AllowOverride All" . fs_filehandler::NewLine();
            $line .= "Require all granted" . fs_filehandler::NewLine();
            $line .= "</Directory>" . fs_filehandler::NewLine();
            $line .= ctrl_options::GetSystemOption( 'dir_index' ) . fs_filehandler::NewLine();
            $line .= "</virtualhost>" . fs_filehandler::NewLine();
            $line .= "# END DOMAIN: " . $rowvhost[ 'vh_name_vc' ] . fs_filehandler::NewLine();
            $line .= "################################################################" . fs_filehandler::NewLine();
        }
    }

    /*
     * ##############################################################################################################
     * #
     * # Write vhost file to disk
     * #
     * ##############################################################################################################
     */

    // write the vhost config file
    $vhconfigfile = ctrl_options::GetSystemOption( 'apache_vhost' );
    if ( fs_filehandler::UpdateFile( $vhconfigfile, 0777, $line ) ) {
        // Reset Apache settings to reflect that config file has been written, until the next change.
        $time = time();
        $vsql = $zdbh->prepare( "UPDATE x_settings
									SET so_value_tx=:time
									WHERE so_name_vc='apache_changed'" );
        $vsql->bindParam( ':time', $time );
        $vsql->execute();
        echo "Finished writting Apache Config... Now reloading Apache..." . fs_filehandler::NewLine();
        if ( sys_versions::ShowOSPlatformVersion() == "Windows" ) {
            $returnValue = ctrl_system::systemCommand(
                    ctrl_options::GetSystemOption( 'httpd_exe' ), ctrl_options::GetSystemOption( 'apache_restart' )
            );
            echo "Apache reload " . ((0 === $returnValue ) ? "suceeded" : "failed") . "." . fs_filehandler::NewLine();
        }
        else {
            
            $command = ctrl_options::GetSystemOption( 'zsudo' );
            $args = array(
                "service",
                ctrl_options::GetSystemOption( 'apache_sn' ),
                ctrl_options::GetSystemOption( 'apache_restart' )
            );
            $returnValue = ctrl_system::systemCommand( $command, $args );

            echo "Apache reload " . ((0 === $returnValue ) ? "suceeded" : "failed") . "." . fs_filehandler::NewLine();
        }

        
    }
    else {
        return false;
    }
}

function CheckErrorDocument( $error )
{
    $errordocs = array( 100, 101, 102, 200, 201, 202, 203, 204, 205, 206, 207,
        300, 301, 302, 303, 304, 305, 306, 307, 400, 401, 402,
        403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413,
        414, 415, 416, 417, 418, 419, 420, 421, 422, 423, 424,
        425, 426, 500, 501, 502, 503, 504, 505, 506, 507, 508,
        509, 510 );
    if ( in_array( $error, $errordocs ) ) {
        return true;
    }
    else {
        return false;
    }
}

function BackupVhostConfigFile()
{
    echo "Apache VHost backups are enabled... Backing up current vhost.conf to: " . ctrl_options::GetSystemOption( 'apache_budir' ) . fs_filehandler::NewLine();
    if ( !is_dir( ctrl_options::GetSystemOption( 'apache_budir' ) ) ) {
        fs_director::CreateDirectory( ctrl_options::GetSystemOption( 'apache_budir' ) );
    }
    copy( ctrl_options::GetSystemOption( 'apache_vhost' ), ctrl_options::GetSystemOption( 'apache_budir' ) . "VHOST_BACKUP_" . time() . "" );
    fs_director::SetFileSystemPermissions( ctrl_options::GetSystemOption( 'apache_budir' ) . ctrl_options::GetSystemOption( 'apache_vhost' ) . ".BU", 0777 );
    if ( ctrl_options::GetSystemOption( 'apache_purgebu' ) == strtolower( "true" ) ) {
        echo "Apache VHost purges are enabled... Purging backups older than: " . ctrl_options::GetSystemOption( 'apache_purge_date' ) . " days..." . fs_filehandler::NewLine();
        echo "[FILE][PURGE_DATE][FILE_DATE][ACTION]" . fs_filehandler::NewLine();
        $purge_date = ctrl_options::GetSystemOption( 'apache_purge_date' );
        if ( $handle     = @opendir( ctrl_options::GetSystemOption( 'apache_budir' ) ) ) {
            while ( false !== ($file = readdir( $handle )) ) {
                if ( $file != "." && $file != ".." ) {
                    $filetime = @filemtime( ctrl_options::GetSystemOption( 'apache_budir' ) . $file );
                    if ( $filetime == NULL ) {
                        $filetime = @filemtime( utf8_decode( ctrl_options::GetSystemOption( 'apache_budir' ) . $file ) );
                    }
                    $filetime = floor( (time() - $filetime) / 86400 );
                    echo "" . $file . " - " . $purge_date . " - " . $filetime . "";
                    if ( $purge_date < $filetime ) {
                        //delete the file
                        echo " - Deleting file..." . fs_filehandler::NewLine();
                        unlink( ctrl_options::GetSystemOption( 'apache_budir' ) . $file );
                    }
                    else {
                        echo " - Skipping file..." . fs_filehandler::NewLine();
                    }
                }
            }
        }
        echo "Purging old backups complete..." . fs_filehandler::NewLine();
    }
    echo "Apache backups complete..." . fs_filehandler::NewLine();
}

function TriggerApacheQuotaUsage()
{
    global $zdbh;
    global $controller;
    $sql      = $zdbh->prepare( "SELECT * FROM x_vhosts WHERE vh_deleted_ts IS NULL" );
    $sql->execute();
    while ( $rowvhost = $sql->fetch() ) {
        if ( $rowvhost[ 'vh_enabled_in' ] == 1 && ctrl_users::CheckUserEnabled( $rowvhost[ 'vh_acc_fk' ] ) ||
            $rowvhost[ 'vh_enabled_in' ] == 1 && ctrl_options::GetSystemOption( 'apache_allow_disabled' ) == strtolower( "true" ) ) {

            //$checksize = $zdbh->query("SELECT * FROM x_bandwidth WHERE bd_month_in = " . date("Ym") . " AND bd_acc_fk = " . $rowvhost['vh_acc_fk'] . "")->fetch();

            $date      = date( "Ym" );
            $findsize  = $zdbh->prepare( "SELECT * FROM x_bandwidth WHERE bd_month_in = :date AND bd_acc_fk = :acc" );
            $findsize->bindParam( ':date', $date );
            $findsize->bindParam( ':acc', $rowvhost[ 'vh_acc_fk' ] );
            $findsize->execute();
            $checksize = $findsize->fetch();
            if(!$checksize) continue;

            $currentuser = ctrl_users::GetUserDetail( $rowvhost[ 'vh_acc_fk' ] );
            if ( $checksize[ 'bd_diskover_in' ] != $checksize[ 'bd_diskcheck_in' ] && $checksize[ 'bd_diskover_in' ] == 1 ) {
                echo "Disk usage over quota, triggering Apache..." . fs_filehandler::NewLine();
                $updateapache = $zdbh->prepare( "UPDATE x_settings SET so_value_tx = 'true' WHERE so_name_vc ='apache_changed'" );
                $updateapache->execute();

                //$updateapache = $zdbh->query("UPDATE x_bandwidth SET bd_diskcheck_in = 1 WHERE bd_acc_fk =" . $rowvhost['vh_acc_fk'] . "");
                $updateapache2 = $zdbh->prepare( "UPDATE x_bandwidth SET bd_diskcheck_in = 1 WHERE bd_acc_fk = :acc" );
                $updateapache2->bindParam( ':acc', $rowvhost[ 'vh_acc_fk' ] );
                $updateapache2->execute();
            }
            if ( $checksize[ 'bd_diskover_in' ] != $checksize[ 'bd_diskcheck_in' ] && $checksize[ 'bd_diskover_in' ] == 0 ) {
                echo "Disk usage under quota, triggering Apache..." . fs_filehandler::NewLine();
                $updateapache = $zdbh->prepare( "UPDATE x_settings SET so_value_tx = 'true' WHERE so_name_vc ='apache_changed'" );
                $updateapache->execute();

                //$updateapache = $zdbh->query("UPDATE x_bandwidth SET bd_diskcheck_in = 0 WHERE bd_acc_fk =" . $rowvhost['vh_acc_fk'] . "");
                $updateapache2 = $zdbh->prepare( "UPDATE x_bandwidth SET bd_diskcheck_in = 0 WHERE bd_acc_fk = :acc" );
                $updateapache2->bindParam( ':acc', $rowvhost[ 'vh_acc_fk' ] );
                $updateapache2->execute();
            }
            if ( $checksize[ 'bd_transover_in' ] != $checksize[ 'bd_transcheck_in' ] && $checksize[ 'bd_transover_in' ] == 1 ) {
                echo "Bandwidth usage over quota, triggering Apache..." . fs_filehandler::NewLine();
                $updateapache = $zdbh->prepare( "UPDATE x_settings SET so_value_tx = 'true' WHERE so_name_vc ='apache_changed'" );
                $updateapache->execute();

                //$updateapache = $zdbh->query("UPDATE x_bandwidth SET bd_transcheck_in = 1 WHERE bd_acc_fk =" . $rowvhost['vh_acc_fk'] . "");
                $updateapache2 = $zdbh->prepare( "UPDATE x_bandwidth SET bd_transcheck_in = 1 WHERE bd_acc_fk = :acc" );
                $updateapache2->bindParam( ':acc', $rowvhost[ 'vh_acc_fk' ] );
                $updateapache2->execute();
            }
            if ( $checksize[ 'bd_transover_in' ] != $checksize[ 'bd_transcheck_in' ] && $checksize[ 'bd_transover_in' ] == 0 ) {
                echo "Bandwidth usage under quota, triggering Apache..." . fs_filehandler::NewLine();
                $updateapache = $zdbh->prepare( "UPDATE x_settings SET so_value_tx = 'true' WHERE so_name_vc ='apache_changed'" );
                $updateapache->execute();

                //$updateapache = $zdbh->query("UPDATE x_bandwidth SET bd_transcheck_in = 0 WHERE bd_acc_fk =" . $rowvhost['vh_acc_fk'] . "");
                $updateapache2 = $zdbh->prepare( "UPDATE x_bandwidth SET bd_transcheck_in = 0 WHERE bd_acc_fk = :acc" );
                $updateapache2->bindParam( ':acc', $rowvhost[ 'vh_acc_fk' ] );
                $updateapache2->execute();
            }
        }
    }
}

?>