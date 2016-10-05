Vagrant.require_version ">= 1.5"

unless Vagrant.has_plugin?('vagrant-hostmanager')
    raise "vagrant-hostmanager is not installed! Please run\n  vagrant plugin install vagrant-hostmanager\n\n"
end
$root = File.dirname(File.expand_path(__FILE__))

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
          vb.customize ["modifyvm", :id, "--hostonlyadapter2", "vboxnet0"]
        end
        config.hostmanager.ip_resolver = proc do |vm, resolving_vm|
            if vm.id
                `VBoxManage guestproperty get #{vm.id} "/VirtualBox/GuestInfo/Net/1/V4/IP"`.split()[1]
            end
        end
    end
end

# By default, the name of the VM is the project's directory name
$hostname = File.basename($root).downcase
$hostIps = `ip addr show | grep inet | grep -v inet6 | cut -d' ' -f6 | cut -d'/' -f1`.split("\n");

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
        config.vm.provision "ansible" do |ansible|
            ansible.playbook = "resources/ansible/playbook.yml"
            ansible.limit = 'all'
            ansible.verbose = 'vvv'
            ansible.extra_vars = {
                hostname: $hostname,
                host_addresses: $hostIps,
                postfix: {
                    postfix_domain: $hostname + ".vb"
                }
            }
        end

        config.vm.provision "ansible", run: "always" do |ansible|
            ansible.playbook = "resources/ansible/playbook-always.yml"
            ansible.limit = 'all'
            ansible.verbose = 'v'
            ansible.extra_vars = {
                host_addresses: $hostIps,
                hostname: $hostname
            }
        end
    else
        config.vm.provision :shell, path: "resources/ansible/windows.sh", args: ["default"]
        config.vm.provision :shell, run: "always", path: "resources/ansible/windows-always.sh", args: ["default"]
    end

    config.vm.synced_folder "./", "/vagrant", type: "nfs"
end
