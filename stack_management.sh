#!/bin/bash

# Function to detect the appropriate Docker Compose command
detect_docker_compose_command() {
    if docker compose version &>/dev/null; then
        echo "docker compose"
    else
        echo "docker-compose"
    fi
}

DOCKER_COMPSE_CMD=$(detect_docker_compose_command)

# Function to start the Docker stack
start_stack() {
    echo "Starting the Docker stack..."
    $DOCKER_COMPSE_CMD --env-file .env --env-file env.local up -d
}

# Function to stop the Docker stack
stop_stack() {
    echo "Stopping the MySQL server in the db container..."
    $DOCKER_COMPSE_CMD exec db /usr/bin/mysqladmin -uroot -p"$MYSQL_ROOT_PASSWORD" shutdown

    echo "Stopping the Docker stack..."
    $DOCKER_COMPSE_CMD --env-file .env --env-file env.local down
}

# Check the argument passed to the script
case "$1" in
    start)
        start_stack
        ;;
    stop)
        stop_stack
        ;;
    *)
        echo "Usage: $0 {start|stop}"
        exit 1
esac

exit 0
