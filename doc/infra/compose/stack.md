Certainly! Here's a README for the script that provides an overview of its functionality, usage, and features:

---

# Docker Stack Management Script

This script is an helper designed to manage a Phraseanet Docker compose stack by wrapping docker compose commande. It provides functionalities to start, stop, check, and gather information about the Docker stack, including docker and Phraseanet version checks, log viewing, and environment details.

## Features

- **Start and Stop the Docker Stack**: Easily start or stop your Docker stack with simple commands.
- **Version Checking**: Verify the compatibility of Docker and Docker Compose versions and display Phraseanet version.
- **Environment Information**: Gather detailed information about the environment, including Phraseanet installation status, version details, and internal URLs.
- **Log Viewing**: View logs for all containers or filter logs for a specific container.
- **maintenance pages** enbaling or disabling.


## Usage

### Prerequisites

- Docker and Docker Compose installed on your system.
- Appropriate permissions to execute Docker commands.
- Environment files (`.env`, `env.local`, or `.env.local`) configured with necessary variables.

### Commands

- **Start the Stack**:
  ```bash
  ./stack.sh start
  ```

- **Stop the Stack**:
  ```bash
  ./stack.sh stop
  ```

- **View stack status**:
  - including:
    - Started containers
    - Containers's Started processus
    - RabbitMQ queues inventory
    - Db database and connection (works only with `db` profile)
       
   ```bash
  ./stack.sh status
  ```

- **Display stack version Information and host environnement**:
  ```bash
  ./stack.sh version
  ```

- **View Logs**:
  - For all containers:
    ```bash
    ./stack.sh logs
    ```
  - For a specific container:
    ```bash
    ./stack.sh logs <container_name>
    ```

- **activate Maintenance**:
  - enabled or disabled the maintenance page
    ```bash
    ./stack.sh maintenance <on|off>
    ```
- **apply-setup**:
  - start setup container for configuration change applying change
    ```bash
    ./stack.sh apply-status
    ```
### Environment Variables

The script uses the following environment variables, which should be defined in your environment files:

- `PHRASEANET_DOCKER_TAG`: The Docker tag for the Phraseanet image.
- `PHRASEANET_DOCKER_REGISTRY`: The Docker registry for the Phraseanet image.
- `PHRASEANET_HOSTNAME`: The hostname for the Phraseanet instance.
- `PHRASEANET_SCHEME`: The scheme (e.g., `http` or `https`) for the Phraseanet instance.
- `PHRASEANET_APP_PORT`: The port for the Phraseanet application.

### Notes

- Ensure that the environment files (`.env`, `env.local`, or `.env.local`) are correctly configured with the necessary variables.
- The script checks for the presence of `config/configuration.yml` to determine if Phraseanet is installed.
- The script constructs the internal URL of the Phraseanet instance using the environment variables `PHRASEANET_HOSTNAME`, `PHRASEANET_SCHEME`, and `PHRASEANET_APP_PORT`.

