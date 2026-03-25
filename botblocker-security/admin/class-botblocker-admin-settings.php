<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BotBlockerAdminSettingsTrait {
    
    public string $empty;
    public string $botblockerUrl;
    public string $version;
    public string $custom_avatar;
    public string $dirs_public;
    public string $dirs_languages;
    public string $dirs_includes;
    public string $dirs_admin;
    public string $dirs_data;
    public string $dirs_vendor;
    public string $pages_dashboard;
    public string $pages_settings;
    public string $pages_integrations;
    public string $pages_reports;
    public string $pages_rules;
    public string $pages_tools;
    public string $pages_cloud_api;
    public string $pages_setup;
    public string $pages_about;
    public string $pages_addons;
    public string $pages_wizard;
    public string $files_IPv4;
    public string $files_IPv6;
    
    private function load_settings(): void
    {
        $this->load_core_settings();
        $this->load_dirs();
        $this->load_pages();
        $this->load_files();
        $this->load_derived_settings();
    }
    
    private function load_core_settings(): void
    {
        $this->empty = BOTBLOCKER_EMPTY;
        $this->botblockerUrl = BOTBLOCKER_URL;
        $this->version = BOTBLOCKER_VERSION;
    }
    
    private function load_dirs(): void
    {
        $this->dirs_public = BOTBLOCKER_DIR . 'public/';
        $this->dirs_languages = BOTBLOCKER_DIR . 'languages/';
        $this->dirs_includes = BOTBLOCKER_DIR . 'includes/';
        $this->dirs_admin = BOTBLOCKER_DIR . 'admin/';
        $this->dirs_data = BOTBLOCKER_DIR . 'data/';
        $this->dirs_vendor = BOTBLOCKER_DIR . 'vendor/';
    }
    
    private function load_pages(): void
    {
        $this->pages_dashboard = bbcs_admin_page_url('bbcs_dashboard');
        $this->pages_settings = bbcs_admin_page_url('bbcs_settings');
        $this->pages_integrations = bbcs_admin_page_url('bbcs_integrations');
        $this->pages_reports = bbcs_admin_page_url('bbcs_reports');
        $this->pages_rules = bbcs_admin_page_url('bbcs_rules');
        $this->pages_tools = bbcs_admin_page_url('bbcs_tools');
        $this->pages_cloud_api = bbcs_admin_page_url('bbcs_cloud_api');
        $this->pages_setup = bbcs_admin_page_url('bbcs_setup_guide');
        $this->pages_about = bbcs_admin_page_url('bbcs_about');
        $this->pages_addons = bbcs_admin_page_url('bbcs_addons');
        $this->pages_wizard = bbcs_site_admin_page_url('bbcs_setup_wizard');
    } 
    
    private function load_files(): void
    {
        $this->files_IPv4 = $this->botblockerUrl . 'data/BotBlocker-test-IPv4-list.txt';
        $this->files_IPv6 = $this->botblockerUrl . 'data/BotBlocker-test-IPv6-list.txt';
    }
    
    private function load_derived_settings(): void
    {
        $this->custom_avatar = $this->botblockerUrl . 'admin/img/avatar.png';
    }
}
