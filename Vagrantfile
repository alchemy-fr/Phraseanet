Vagrant.require_version ">= 1.5"

class MyCustomError < StandardError
	attr_reader :code

	def initialize(code)
		@code = code
	end

	def to_s
	"[#{code} #{super}]"
	end
end
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

$php = [ "5.6", "7.0", "7.1", "7.2" ]
$phpVersion = ENV['phpversion'] ? ENV['phpversion'] : "7.0";

unless Vagrant.has_plugin?('vagrant-hostmanager')
    raise "vagrant-hostmanager is not installed! Please run\n  vagrant plugin install vagrant-hostmanager\n\n"
end

unless $php.include?($phpVersion)
    raise "You should specify php version before running vagrant\n\n (Available : 5.6, 7.0, 7.1, 7.2 | default => 5.6)\n\n Exemple: phpversion='7.0' vagrant up \n\n"
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
		# raise MyCustomError.new($hostIps), "HOST IP"

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

    config.vm.box = "ubuntu/trusty64"

    config.ssh.forward_agent = true
    config_net(config)

    # If ansible is in your path it will provision from your HOST machine
    # If ansible is not found in the path it will be instaled in the VM and provisioned from there
    if which('ansible-playbook')
        config.vm.provision "ansible_local" do |ansible|
            ansible.playbook = "resources/ansible/playbook.yml"
            ansible.limit = 'all'
            ansible.verbose = 'vvv'
            ansible.extra_vars = {
                hostname: $hostname,
                host_addresses: $hostIps,
                phpversion: $phpVersion,
                postfix: {
                    postfix_domain: $hostname + ".vb"
                }
            }
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
		# raise MyCustomError.new([$hostname, $phpVersion, $hostIps]), "HOST IP"
		# raise MyCustomError.new($hostIps), "HOST IP"
		# raise MyCustomError.new($hostIps), "HOST IP"

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
