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
check_versions() {
    local required_docker_version="25.0.5"
    local required_compose_version="2.29.0"

    # Get Docker version
    local docker_version=$(docker --version | awk -F'[ ,]' '{print $3}')
    if [ "$(printf '%s\n' "$required_docker_version" "$docker_version" | sort -V | head -n1)" != "$required_docker_version" ]; then
        echo "Error: Docker version $docker_version is less than the required version $required_docker_version."
        exit 1
    fi

    # Get Docker Compose version
    local compose_version=$($DOCKER_COMPSE_CMD version --short)
    if [ "$(printf '%s\n' "$required_compose_version" "$compose_version" | sort -V | head -n1)" != "$required_compose_version" ]; then
        echo "Error: Docker Compose version $compose_version is less than the required version $required_compose_version."
        exit 1
    fi

    echo "Docker and Docker Compose versions are compatible."

    # Get uptime of the stack
    echo "Stack Uptime:"
    $DOCKER_COMPSE_CMD ps | awk 'NR>1 {print $4}'

    # Get internal IP addresses
    echo "Internal IP Addresses:"
    $DOCKER_COMPSE_CMD exec -T db sh -c 'ip addr show eth0 | grep "inet " | awk "{print \$2}" | cut -d/ -f1'

    # Get container status
    echo "Container Status:"
    $DOCKER_COMPSE_CMD ps

    # Get resource usage
    echo "Resource Usage:"
    $DOCKER_COMPSE_CMD top
}

# Function to display information about the environment
display_info() {
    echo "Checking environment information..."

    # Load environment variables
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

    # Construct and display the internal URL of the Phraseanet instance
    if [ -n "$PHRASEANET_HOSTNAME" ] && [ -n "$PHRASEANET_SCHEME" ] && [ -n "$PHRASEANET_APP_PORT" ]; then
        local internal_url="${PHRASEANET_SCHEME}://${PHRASEANET_HOSTNAME}:${PHRASEANET_APP_PORT}"
        echo "Internal URL of Phraseanet Instance: $internal_url"
    else
        echo "Internal URL of Phraseanet Instance: Cannot be determined (missing environment variables)."
    fi

    # Check if Phraseanet is installed by looking for config/configuration.yml
    if [ -f "config/configuration.yml" ]; then
        echo "Phraseanet is installed: config/configuration.yml found."

        # Get creation date of the configuration file
        local creation_date=$(date -r "$(stat -f %B "config/configuration.yml" 2>/dev/null || stat -c %Y "config/configuration.yml" 2>/dev/null)" "+%Y-%m-%d %H:%M:%S" 2>/dev/null || echo "Unknown")
        echo "Installation Date: $creation_date"

        # Check for the compiled configuration file and get its last modification date
        if [ -f "config/configuration-compiled.php" ]; then
            local last_modified_date=$(date -r "$(stat -f %m "config/configuration-compiled.php" 2>/dev/null || stat -c %Y "config/configuration-compiled.php" 2>/dev/null)" "+%Y-%m-%d %H:%M:%S" 2>/dev/null || echo "Unknown")
            echo "Last Update Date: $last_modified_date"
        else
            echo "config/configuration-compiled.php not found."
        fi

        # Check if the Phraseanet container is running
        if $DOCKER_COMPSE_CMD ps | grep -q "phraseanet.*Up"; then
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
            local version_from_console=$($DOCKER_COMPSE_CMD exec phraseanet sh -c 'bin/console --version | grep -o "KONSOLE KOMMANDER version [^ ]* [^ ]*" | awk "{print \$NF}"')
            echo "Version from console: $version_from_console"

            # Compare versions
            if [ "$version_from_file" == "$version_from_console" ]; then
                echo "Versions match."
            else
                echo "Versions do not match."
            fi
        else
            echo "Phraseanet container is not running. Cannot fetch version information."
        fi
    else
        echo "Phraseanet is not installed: config/configuration.yml not found."
    fi
}

# Function to display logs
display_logs() {
    if [ -n "$1" ]; then
        echo "Displaying logs for container: $1"
        $DOCKER_COMPSE_CMD logs -f "$1"
    else
        echo "Displaying logs for all containers"
        $DOCKER_COMPSE_CMD logs -f
    fi
}

DOCKER_COMPSE_CMD=$(detect_docker_compose_command)

# Function to start the Docker stack
start_stack() {
    echo "Starting the Docker stack..."
    local env_files=($(get_env_files))
    $DOCKER_COMPSE_CMD "${env_files[@]/#/--env-file=}" up -d
}

# Function to stop the Docker stack
stop_stack() {
    echo "Stopping the MySQL server in the db container..."
    # Execute the mysqladmin command inside the container where the environment variable is defined
    $DOCKER_COMPSE_CMD exec db sh -c '/usr/bin/mysqladmin -uroot -p"$MYSQL_ROOT_PASSWORD" shutdown'

    echo "Stopping the Docker stack..."
    local env_files=($(get_env_files))
    $DOCKER_COMPSE_CMD "${env_files[@]/#/--env-file=}" down
}

# Check the argument passed to the script
case "$1" in
    start)
        start_stack
        ;;
    stop)
        stop_stack
        ;;
    check)
        check_versions
        ;;
    info)
        display_info
        ;;
    log)
        display_logs "$2"
        ;;
    *)
        echo "Usage: $0 {start|stop|check|info|log [container_name]}"
        exit 1
esac

exit 0
