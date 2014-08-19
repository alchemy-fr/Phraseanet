require 'yaml'

root = File.dirname(File.expand_path(__FILE__))

Vagrant.configure("2") do |config|
    Dir.glob(root+"/vagrant/vms/**/puphpet/config.yaml").each do|f|
        dir = File.dirname(File.expand_path(f)+"/..")
        base_path = dir[0..-21]
        configValues = YAML.load_file(f)
        data = configValues['vagrantfile-local']

        config.vm.define "vm-#{data['name']}" do |node|
            node.vm.box = "#{data['vm']['box']}"
            node.vm.box_url = "#{data['vm']['box_url']}"

            if data['vm']['hostname'].to_s.strip.length != 0
                node.vm.hostname = "#{data['vm']['hostname']}"
            end

            node.vm.provider :virtualbox do |vb|
                vb.name = "#{data['name']}"
            end

            if data['vm']['network']['private_network'].to_s != ''
                node.vm.network "private_network", ip: "#{data['vm']['network']['private_network']}"
            end

            data['vm']['network']['forwarded_port'].each do |i, port|
            
            if port['guest'] != '' && port['host'] != ''
              node.vm.network :forwarded_port, guest: port['guest'].to_i, host: port['host'].to_i
            end
        end

        if Vagrant.has_plugin?('vagrant-hostsupdater')
            hosts = Array.new()

            if !configValues['apache']['install'].nil? &&
                configValues['apache']['install'].to_i == 1 &&
                    configValues['apache']['vhosts'].is_a?(Hash)
                    configValues['apache']['vhosts'].each do |i, vhost|
                    hosts.push(vhost['servername'])
            
                if vhost['serveraliases'].is_a?(Array)
                vhost['serveraliases'].each do |vhost_alias|
                hosts.push(vhost_alias)
                end
                end
                end
            elsif !configValues['nginx']['install'].nil? &&
                   configValues['nginx']['install'].to_i == 1 &&
                   configValues['nginx']['vhosts'].is_a?(Hash)
              configValues['nginx']['vhosts'].each do |i, vhost|
                hosts.push(vhost['server_name'])

                if vhost['server_aliases'].is_a?(Array)
                  vhost['server_aliases'].each do |x, vhost_alias|
                    hosts.push(vhost_alias)
                  end
                end
              end
            end

            if hosts.any?
              contents = File.open("#{dir}/puphpet/shell/hostsupdater-notice.txt", 'r'){ |file| file.read }
              puts "\n\033[34m#{contents}\033[0m\n"

              if node.vm.hostname.to_s.strip.length == 0
                node.vm.hostname = 'puphpet-dev-machine'
              end

              node.hostsupdater.aliases = hosts
            end
            end

            data['vm']['synced_folder'].each do |i, folder|
            if folder['source'] == ''
                folder['source'] = root
            end
            if folder['source'] != '' && folder['target'] != ''
              if folder['sync_type'] == 'nfs'
                node.vm.synced_folder "#{folder['source']}", "#{folder['target']}", id: "#{i}", type: "nfs", mount_options: ['rw', 'vers=3', 'tcp', 'fsc']
              elsif folder['sync_type'] == 'smb'
                node.vm.synced_folder "#{folder['source']}", "#{folder['target']}", id: "#{i}", type: "smb"
              elsif folder['sync_type'] == 'rsync'
                rsync_args = !folder['rsync']['args'].nil? ? folder['rsync']['args'] : ["--verbose", "--archive", "--delete", "-z"]
                rsync_auto = !folder['rsync']['auto'].nil? ? folder['rsync']['auto'] : true
                rsync_exclude = !folder['rsync']['exclude'].nil? ? folder['rsync']['exclude'] : [".vagrant/"]

                node.vm.synced_folder "#{folder['source']}", "#{folder['target']}", id: "#{i}",
                    rsync__args: rsync_args, rsync__exclude: rsync_exclude, rsync__auto: rsync_auto, type: "rsync"
              else
                node.vm.synced_folder "#{folder['source']}", "#{folder['target']}", id: "#{i}",
                  group: 'www-data', owner: 'www-data', mount_options: ["dmode=775", "fmode=764"]
              end
            end
            end

            node.vm.usable_port_range = (10200..10500)

            if data['vm']['chosen_provider'].empty? || data['vm']['chosen_provider'] == "virtualbox"
            ENV['VAGRANT_DEFAULT_PROVIDER'] = 'virtualbox'

            node.vm.provider :virtualbox do |virtualbox|
              data['vm']['provider']['virtualbox']['modifyvm'].each do |key, value|
                if key == "memory"
                  next
                end

                if key == "natdnshostresolver1"
                  value = value ? "on" : "off"
                end

                virtualbox.customize ["modifyvm", :id, "--#{key}", "#{value}"]
              end

              virtualbox.customize ["modifyvm", :id, "--memory", "#{data['vm']['memory']}"]

              if data['vm']['hostname'].to_s.strip.length != 0
                virtualbox.customize ["modifyvm", :id, "--name", node.vm.hostname]
              end
            end
            end

            if data['vm']['chosen_provider'] == "vmware_fusion" || data['vm']['chosen_provider'] == "vmware_workstation"
            ENV['VAGRANT_DEFAULT_PROVIDER'] = (data['vm']['chosen_provider'] == "vmware_fusion") ? "vmware_fusion" : "vmware_workstation"

            node.vm.provider "vmware_fusion" do |v|
              data['vm']['provider']['vmware'].each do |key, value|
                if key == "memsize"
                  next
                end

                v.vmx["#{key}"] = "#{value}"
              end

              v.vmx["memsize"] = "#{data['vm']['memory']}"

              if data['vm']['hostname'].to_s.strip.length != 0
                v.vmx["displayName"] = node.vm.hostname
              end
            end
            end

            if data['vm']['chosen_provider'] == "parallels"
            ENV['VAGRANT_DEFAULT_PROVIDER'] = "parallels"

            node.vm.provider "parallels" do |v|
                data['vm']['provider']['parallels'].each do |key, value|
                if key == "memsize"
                    next
                end
                
                v.customize ["set", :id, "--#{key}", "#{value}"]
            end
                
            v.memory = "#{data['vm']['memory']}"
            v.cpus = "#{data['vm']['cpus']}"

            if data['vm']['hostname'].to_s.strip.length != 0
                v.name = node.vm.hostname
                end
                end
            end

            ssh_username = !data['ssh']['username'].nil? ? data['ssh']['username'] : "vagrant"

            node.vm.provision "shell" do |s|
                s.path = "#{base_path}/puphpet/shell/initial-setup.sh"
                s.args = "/vagrant/vagrant/vms/#{data['name']}/puphpet"
            end

            node.vm.provision "shell" do |kg|
                kg.path = "#{base_path}/puphpet/shell/ssh-keygen.sh"
                kg.args = "#{ssh_username}"
            end
            
            node.vm.provision :shell, :path => "#{base_path}/puphpet/shell/update-puppet.sh"

            node.vm.provision :puppet do |puppet|
                puppet.facter = {
                    "ssh_username"     => "#{ssh_username}",
                    "provisioner_type" => ENV['VAGRANT_DEFAULT_PROVIDER'],
                    "vm_target_key"    => 'vagrantfile-local',
                }
                puppet.manifests_path = "#{data['vm']['provision']['puppet']['manifests_path']}"
                puppet.manifest_file = "#{data['vm']['provision']['puppet']['manifest_file']}"
                puppet.module_path = "#{data['vm']['provision']['puppet']['module_path']}"

                if !data['vm']['provision']['puppet']['options'].empty?
                      puppet.options = data['vm']['provision']['puppet']['options']
                end
            end

            node.vm.provision :shell do |s|
                s.path = "#{base_path}/puphpet/shell/execute-files.sh"
                s.args = ["exec-once", "exec-always"]
            end

            node.vm.provision :shell, run: "always" do |s|
                s.path = "#{base_path}/puphpet/shell/execute-files.sh"
                s.args = ["startup-once", "startup-always"]
            end

            node.vm.provision :shell, :path => "#{base_path}/puphpet/shell/important-notices.sh"

            if File.file?("#{dir}/puphpet/files/dot/ssh/id_rsa")
                node.ssh.private_key_path = [
                  "#{dir}/puphpet/files/dot/ssh/id_rsa",
                  "#{dir}/puphpet/files/dot/ssh/insecure_private_key"
                ]
            end

            if !data['ssh']['host'].nil?
                node.ssh.host = "#{data['ssh']['host']}"
            end
            if !data['ssh']['port'].nil?
                node.ssh.port = "#{data['ssh']['port']}"
            end
            if !data['ssh']['username'].nil?
                node.ssh.username = "#{data['ssh']['username']}"
            end
            if !data['ssh']['guest_port'].nil?
                node.ssh.guest_port = data['ssh']['guest_port']
            end
            if !data['ssh']['shell'].nil?
                node.ssh.shell = "#{data['ssh']['shell']}"
            end
            if !data['ssh']['keep_alive'].nil?
                node.ssh.keep_alive = data['ssh']['keep_alive']
            end
            if !data['ssh']['forward_agent'].nil?
                node.ssh.forward_agent = data['ssh']['forward_agent']
            end
            if !data['ssh']['forward_x11'].nil?
                node.ssh.forward_x11 = data['ssh']['forward_x11']
            end
            if !data['vagrant']['host'].nil?
                node.vagrant.host = data['vagrant']['host'].gsub(":", "").intern
            end
        end
    end
end
