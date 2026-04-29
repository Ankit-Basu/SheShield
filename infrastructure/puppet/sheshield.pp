# ─────────────────────────────────────────────────
# SheShield - Puppet Manifest
# Enforces desired state on application servers
# ─────────────────────────────────────────────────

class sheshield {

  # ─── Ensure packages are installed ──────────────
  package { ['apache2', 'php8.2', 'php8.2-mysql', 'php8.2-curl', 'php8.2-gd']:
    ensure => installed,
  }

  # ─── Ensure Apache service is running ───────────
  service { 'apache2':
    ensure  => running,
    enable  => true,
    require => Package['apache2'],
  }

  # ─── Ensure upload directories exist ────────────
  file { '/var/www/html/sheshield/uploads':
    ensure  => directory,
    owner   => 'www-data',
    group   => 'www-data',
    mode    => '0777',
    recurse => true,
    require => Package['apache2'],
  }

  file { '/var/www/html/sheshield/uploads/evidence':
    ensure  => directory,
    owner   => 'www-data',
    group   => 'www-data',
    mode    => '0777',
    require => File['/var/www/html/sheshield/uploads'],
  }

  file { '/var/www/html/sheshield/uploads/profile_images':
    ensure  => directory,
    owner   => 'www-data',
    group   => 'www-data',
    mode    => '0777',
    require => File['/var/www/html/sheshield/uploads'],
  }

  file { '/var/www/html/sheshield/logs':
    ensure  => directory,
    owner   => 'www-data',
    group   => 'www-data',
    mode    => '0777',
    require => Package['apache2'],
  }

  # ─── Ensure Apache rewrite module is enabled ────
  exec { 'enable-rewrite':
    command => '/usr/sbin/a2enmod rewrite',
    creates => '/etc/apache2/mods-enabled/rewrite.load',
    notify  => Service['apache2'],
  }

  # ─── Ensure correct ownership ──────────────────
  exec { 'set-ownership':
    command => '/bin/chown -R www-data:www-data /var/www/html/sheshield',
    require => Package['apache2'],
  }
}

# Apply the class
include sheshield
