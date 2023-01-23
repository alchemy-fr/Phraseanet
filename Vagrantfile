Vagrant.require_version ">= 1.5"
require 'json'

# Check to determine whether we're on a windows or linux/os-x host,
# later on we use this to launch ansible in the supported way
# source: https://stackoverflow.com/questions/2108727/which-in-ruby-checking-if-program-exists-in-path-from-ruby
def which(cmd)
    exts = ENV['PATHEXT'] ? ENV['PATHEXT'].split(';') : ['']
    ENV['PATH'].split(File::PATH_SEPARATOR).each do |path|
        exts.each { |ext|
            exe = File.join(path, "#{cmd}#{ext}")
            return exe if File.executable? exe
        }
    end
    return nil
end

if which('ip')
    $env = "mac"
else if which('ifconfig')
        $env = "linux"
    else
        $env = "windows"
    end
end

unless Vagrant.has_plugin?('vagrant-hostmanager')
    raise "vagrant-hostmanager is not installed! Please run\n  vagrant plugin install vagrant-hostmanager\n\n"
end

# Check to determine if box_meta JSON is present
# if provisionned : pick name of box
if File.file?(".vagrant/machines/default/virtualbox/box_meta")
    data = File.read(".vagrant/machines/default/virtualbox/box_meta")
    parsed_json = JSON.parse(data)
    $box = parsed_json["name"]
end

# if not : run prompt to configure provisioning
if !File.file?(".vagrant/machines/default/virtualbox/box_meta") && ARGV[0] == 'up'
    print "\033[34m \nChoose a Build type :\n\n(1) Use prebuilt Phraseanet Box\n(2) Build Phraseanet from scratch (xenial)\n\033[00m"
    type = STDIN.gets.chomp
    print "\n"
    # Switch between Phraseanet box and native trusty64
    case (type)
       when '1'
            $box = "alchemy/Phraseanet-vagrant-dev_php"
            $playbook = "resources/ansible/playbook-boxes.yml"
       when '2'
            $box = "ubuntu/xenial64"
            $playbook = "resources/ansible/playbook.yml"
       else
            raise "\033[31mYou should specify Build type before running vagrant\n\n (Available : 1, 2)\n\n\033[00m"
    end
    print "\033[32m-----------------------------------------------\n"
    print "Build with "+$box+" box\n"
    print "-----------------------------------------------\n\n\033[00m"

    print "\033[34mChoose a PHP version for your build (Available : 5.6, 7.0, 7.1, 7.2)\n\033[00m"
    phpversion = STDIN.gets.chomp
    print "\n"
    # Php version selection
    case (phpversion)
        when "5.6", "7.0", "7.1", "7.2"
            print "\033[32mSelected PHP version : "+phpversion+"\n\033[00m"
            print "Continue ? (Y/n) \n"
            continue = STDIN.gets.chomp
            case continue
               when 'n', 'no', 'N', 'NO'
                  raise "\033[31mBuild aborted\033[00m"
               else
               if (type == '1')
                  $box.concat(phpversion)
               end
                  print "\033[32m-----------------------------------------------\n"
                  print "Build with PHP"+phpversion+"\n"
                  print "-----------------------------------------------\n\n\033[00m"

            end
        else
            raise "\033[31mYou should specify php version before running vagrant\n\n (Available : 5.6, 7.0, 7.1, 7.2)\n\n\033[00m"
    end
end

$root = File.dirname(File.expand_path(__FILE__))

def config_net(config)
    config.hostmanager.aliases = [
        $hostname + ".vb",
        "www." + $hostname + ".vb",
        "dev." + $hostname + ".vb"
    ]

    # Assign static IP if present in network config
    if File.file?($root + "/.network.conf")
        ipAddress = File.read($root + "/.network.conf")
        #config.vm.network :private_network, ip: ipAddress
    else
        # vboxnet0 can be changed to use a specific private_network
        config.vm.network :private_network, type: "dhcp"
        config.vm.provider "virtualbox" do |vb|

          if $env == "mac" || $env == "linux"
              vb.customize ["modifyvm", :id, "--hostonlyadapter2", "vboxnet0"]
          else
              vb.customize ["modifyvm", :id, "--hostonlyadapter2", "VirtualBox Host-Only Ethernet Adapter"]
			  vb.customize ["setextradata", :id, "VBoxInternal2/SharedFoldersEnableSymlinksCreate/vagrant", "1"]
          end
        end

        config.vm.network :public_network, bridge:"en0: Ethernet"
        
        config.hostmanager.ip_resolver = proc do |vm, resolving_vm|
            if vm.id
                if $env == "mac" || $env == "linux"
                   `VBoxManage guestproperty get #{vm.id} "/VirtualBox/GuestInfo/Net/1/V4/IP"`.split()[1]
                else
                   `"C:/Program Files/Oracle/VirtualBox/VBoxManage" guestproperty get #{vm.id} "/VirtualBox/GuestInfo/Net/1/V4/IP"`.split()[1]
                end
            end
        end
    end
end

# By default, the name of the VM is the project's directory name
$hostname = File.basename($root).downcase

if $env == "mac"
    # $hostIps = `ip addr show | grep inet | grep -v inet6 | cut -d' ' -f6 | cut -d'/' -f1`.split("\n");
    $hostIps = `ip addr show | sed -nE 's/[[:space:]]*inet ([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})(.*)$/\\1/p'`.split("\n");
else if $env == "linux"
        $hostIps = `ifconfig | sed -nE 's/[[:space:]]*inet ([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})(.*)$/\\1/p'`.split("\n");
    else
        $hostIps = `resources/ansible/inventories/GetIpAdresses.cmd`;
    end
end

Vagrant.configure("2") do |config|
    # Configure hostmanager
    config.hostmanager.enabled = true
    config.hostmanager.manage_host = true
    config.hostmanager.ignore_private_ip = false
    config.hostmanager.include_offline = true

    config.vm.hostname = $hostname

    config.vm.provider :virtualbox do |v|
        v.name = $hostname
        v.customize [
            "modifyvm", :id,
            "--name", $hostname,
            "--memory", 4096,
            "--cpus", 2,
        ]
    end

    config.vm.box = $box
    config.ssh.forward_agent = true
    config_net(config)

    # If ansible is in your path it will provision from your HOST machine
    # If ansible is not found in the path it will be instaled in the VM and provisioned from there
    if which('ansible-playbook')

        if $playbook
            config.vm.provision "ansible_local" do |ansible|
                ansible.playbook = $playbook
                ansible.limit = 'all'
                ansible.verbose = 'vvv'
                ansible.extra_vars = {
                    hostname: $hostname,
                    host_addresses: $hostIps,
                    phpversion: phpversion,
                    postfix: {
                        postfix_domain: $hostname + ".vb"
                    }
                }
            end
        end

        config.vm.provision "ansible_local", run: "always" do |ansible|
            ansible.playbook = "resources/ansible/playbook-always.yml"
            ansible.limit = 'all'
            ansible.verbose = 'v'
            ansible.extra_vars = {
                host_addresses: $hostIps,
                hostname: $hostname
            }
        end
    else
        config.vm.provision :shell, path: "resources/ansible/windows.sh", args: [$hostname, $phpVersion, $hostIps]
       # config.vm.provision :shell, run: "always", path: "resources/ansible/windows-always.sh", args: ["default"]
    end

    if $env == "mac" || $env == "linux"
        config.vm.synced_folder "./", "/vagrant", type: "nfs", mount_options: ['rw', 'vers=3', 'tcp', 'fsc']
    else
#       config.vm.synced_folder "./", "/vagrant", type: "smb", mount_options: ["vers=3.02","mfsymlinks","noserverino"]
        config.vm.synced_folder "./", "/vagrant"

    end
end
