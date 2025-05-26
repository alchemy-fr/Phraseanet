#!/bin/bash

# Function to detect the appropriate Docker Compose command
detect_docker_compose_command() {
    if docker compose version &>/dev/null; then
        echo "docker compose"
    else
        echo "docker-compose"
    fi
}

# Function to determine the environment files to use
get_env_files() {
    local env_files=(".env")
    for envFile in ".env.local" "env.local"; do
        if [ -f "$envFile" ]; then
            env_files+=("$envFile")
            break
        fi
    done
    echo "${env_files[@]}"
}

# Function to check Docker and Docker Compose versions and display additional information
stack_status() {
    check_compose_version

    # Get container status
    echo "Container Status:"
    $DOCKER_COMPOSE_CMD ps
    echo

    # Get resource usage
    echo "Resource Usage:"
    $DOCKER_COMPOSE_CMD top
    echo

    display_rabbitmq_info
    echo 

    display_db_info
}

# Function to display information about the environment
display_info() {
    echo "Checking environment information..."
    echo

    # Kernel version (Linux and macOS compatible)
    echo "Kernel Version:"
    uname -sr
    echo
    
    check_compose_version

    local env_files=($(get_env_files))
    for env_file in "${env_files[@]}"; do
        if [ -f "$env_file" ]; then
            set -a
            source "$env_file"
            set +a
        fi
    done
    
    # Display Docker tag and registry information
    echo "Phraseanet Docker Tag: ${PHRASEANET_DOCKER_TAG:-Not set}"
    echo "Phraseanet Docker Registry: ${PHRASEANET_DOCKER_REGISTRY:-Not set}"
    echo

    # Construct and display the internal URL of the Phraseanet instance
    if [ -n "$PHRASEANET_HOSTNAME" ] && [ -n "$PHRASEANET_SCHEME" ] && [ -n "$PHRASEANET_APP_PORT" ]; then
        local internal_url="${PHRASEANET_SCHEME}://${PHRASEANET_HOSTNAME}:${PHRASEANET_APP_PORT}"
        echo "Internal URL of Phraseanet Instance: $internal_url"
        echo
    else
        echo "Internal URL of Phraseanet Instance: Cannot be determined (missing environment variables)."
        echo
    fi

    # Check if Phraseanet is installed by looking for config/configuration.yml
    if [ -f "config/configuration.yml" ]; then
        echo "Phraseanet is installed: config/configuration.yml found."

        # Get creation date of the configuration file
        local creation_date=$(date -r "$(stat -f %B "config/configuration.yml" 2>/dev/null || stat -c %Y "config/configuration.yml" 2>/dev/null)" "+%Y-%m-%d %H:%M:%S" 2>/dev/null || echo "Unknown")
        echo "Installation Date (configuration.yml creation date): $creation_date"

        # Check for the compiled configuration file and get its last modification date
        if [ -f "config/configuration-compiled.php" ]; then
            local last_modified_date=$(date -r "$(stat -f %m "config/configuration-compiled.php" 2>/dev/null || stat -c %Y "config/configuration-compiled.php" 2>/dev/null)" "+%Y-%m-%d %H:%M:%S" 2>/dev/null || echo "Unknown")
            echo "Last Update Date (configuration-compiled.yml update date): $last_modified_date"
            echo
        else
            echo "Last Update unknown, config/configuration-compiled.php not found."
            echo
        fi

        # Check if the Phraseanet container is running
        if $DOCKER_COMPOSE_CMD ps | grep -q "phraseanet.*Up"; then
            echo "Phraseanet container is running. Fetching version information..."

            # Extract version from Version.php file
            local version_php_file="lib/Alchemy/Phrasea/Core/Version.php"
            if [ -f "$version_php_file" ]; then
                local version_from_file=$(grep -o "private \$number = '[^']*" "$version_php_file" | sed "s/private \$number = '//;s/'//")
                echo "Version from Version.php: $version_from_file"
            else
                echo "Version.php file not found."
            fi

            # Execute the command to get Phraseanet version from console
            local version_from_console=$($DOCKER_COMPOSE_CMD exec phraseanet sh -c 'bin/console --version | grep -o "KONSOLE KOMMANDER version [^ ]* [^ ]*" | awk "{print \$NF}"')
            echo "Version from console: $version_from_console"

            # Compare versions
            if [ "$version_from_file" == "$version_from_console" ]; then
                echo "Versions match."
                echo
            else
                echo "Versions do not match."
                echo
            fi
        else
            # Extract version from Version.php file
            local version_php_file="lib/Alchemy/Phrasea/Core/Version.php"
            if [ -f "$version_php_file" ]; then
                local version_from_file=$(grep -o "private \$number = '[^']*" "$version_php_file" | sed "s/private \$number = '//;s/'//")
                echo "Version from Version.php: $version_from_file"
            else
                echo "Version.php file not found."
            fi
            echo "Phraseanet container is not running. Cannot fetch version information from container."
            echo
        fi
    else
        echo "Phraseanet is not installed: config/configuration.yml not found."
        echo
    fi

    # Fetch the latest version from GitHub
    echo "Fetching the latest Phraseanet version from GitHub..."
    local latest_version=$(curl -s https://api.github.com/repos/alchemy-fr/Phraseanet/releases/latest | grep '"tag_name":' | sed -E 's/.*"([^"]+)".*/\1/')
    echo "Latest Phraseanet Version on GitHub: $latest_version"
    echo
}

# Function to display logs
display_logs() {
    if [ -n "$1" ]; then
        echo "Displaying logs for container: $1"
        $DOCKER_COMPOSE_CMD logs -f "$1"
    else
        echo "Displaying logs for all containers"
        $DOCKER_COMPOSE_CMD logs -f
    fi
}

DOCKER_COMPOSE_CMD=$(detect_docker_compose_command)

# Function to start the Docker stack
start_stack() {
    echo "Starting the Docker stack..."
    local env_files=($(get_env_files))
    $DOCKER_COMPOSE_CMD "${env_files[@]/#/--env-file=}" up -d
}

# Function to stop the Docker stack
stop_stack() {
    echo "Stopping the MySQL server in the db container..."
    # Execute the mysqladmin command inside the container where the environment variable is defined
    $DOCKER_COMPOSE_CMD exec db sh -c '/usr/bin/mysqladmin -uroot -p"$MYSQL_ROOT_PASSWORD" shutdown'
    echo

    echo "Stopping the Docker stack..."
    local env_files=($(get_env_files))
    $DOCKER_COMPOSE_CMD "${env_files[@]/#/--env-file=}" down
}

# Function rabbimq queue information
display_rabbitmq_info() {
    echo "RabbitMQ queue informations :"
    $DOCKER_COMPOSE_CMD exec rabbitmq sh -c 'rabbitmqctl --version  & rabbitmqctl list_queues --vhost $PHRASEANET_RABBITMQ_VHOST'
    echo
}

# Function check compose version
check_compose_version() {
    local compose_version=$($DOCKER_COMPOSE_CMD version --short)
    if [[ $compose_version == *"v2"* ]]; then
        echo "Docker Compose v2 detected."
    else
        echo "Docker Compose v1 detected."
    fi

    local required_docker_version="27.3.1"
    local required_compose_version="2.30.3"

    # Get Docker version
    local docker_version=$(docker --version | awk -F'[ ,]' '{print $3}')
    if [ "$(printf '%s\n' "$required_docker_version" "$docker_version" | sort -V | head -n1)" != "$required_docker_version" ]; then
        echo "Error: Docker version $docker_version is less than the required version $required_docker_version."
        exit 1
    fi

    # Get Docker Compose version
    local compose_version=$($DOCKER_COMPOSE_CMD version --short)
    if [ "$(printf '%s\n' "$required_compose_version" "$compose_version" | sort -V | head -n1)" != "$required_compose_version" ]; then
        echo "Error: Docker Compose version $compose_version is less than the required version $required_compose_version."
        exit 1
    fi

    echo "Docker and Docker Compose versions are compatible."
    echo "Docker Version: $docker_version"
    echo "Docker Compose Version: $compose_version"
    echo
}

# Function db status
display_db_info () {
    echo "DB status"
    $DOCKER_COMPOSE_CMD exec db sh -c 'env |grep MYSQL_ & mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SHOW DATABASES;SHOW PROCESSLIST;"'
    echo
}
 # Function to enable or disable maintenance mode
maintenance_mode() {
    if [ "$1" == "on" ]; then
        echo "Enabling maintenance mode..."
        $DOCKER_COMPOSE_CMD exec phraseanet sh -c 'envsubst < "/usr/local/etc/maintenance.html" > /var/alchemy/Phraseanet/datas/nginx/maintenance.html'
        echo "Maintenance mode is now enabled. it will be disabling after the next restart of stack."
        echo "if you want persist maintenance mode, you must set the environment variable PHRASEANET_MAINTENANCE_MODE=1 in your .env file."
    elif [ "$1" == "off" ]; then
        echo "Disabling maintenance mode..."
        $DOCKER_COMPOSE_CMD exec phraseanet sh -c 'rm -rf /var/alchemy/Phraseanet/datas/nginx/maintenance.html'
        echo "Maintenance mode is now disabled."
    else
        echo "Usage: $0 maintenance {on|off}"
        exit 1
    fi
}
# Function to apply setup
apply_setup() {
    echo "Applying setup..."
    local env_files=($(get_env_files))
    $DOCKER_COMPOSE_CMD "${env_files[@]/#/--env-file=}" run --rm setup
    if [ $? -eq 0 ]; then
        echo "Setup applied successfully."
    else
        echo "Failed to apply setup."
        exit 1
    fi
}
# Check the argument passed to the script
case "$1" in
    start)
        start_stack
        ;;
    stop)
        stop_stack
        ;;
    status)
        stack_status
        ;;
    version)
        display_info
        ;;
    logs)
        display_logs "$2"
        ;;
    maintenance)
        if [ -n "$2" ]; then
            maintenance_mode "$2"
        else
            echo "Usage: $0 maintenance {on|off}"
            exit 1
        fi
        ;;
    apply)
        apply_setup
        ;;
    *)
        echo "Usage: $0 {start|stop|status|version|maintenance [on|off]|logs [container_name]}"
        exit 1
esac

exit 0
