# ============================================================
# PRIA SOLO — Docker Compose (Modul 7) + Docker Hub workflow
# ============================================================

DOCKERHUB_USERNAME ?= YOUR_DOCKERHUB_USERNAME
TAG ?= v1
BACKEND_IMAGE_NAME ?= pria-solo-backend
FRONTEND_IMAGE_NAME ?= pria-solo-frontend
BACKEND_LOCAL_IMAGE := $(BACKEND_IMAGE_NAME):$(TAG)
FRONTEND_LOCAL_IMAGE := $(FRONTEND_IMAGE_NAME):$(TAG)
BACKEND_REMOTE_IMAGE := $(DOCKERHUB_USERNAME)/$(BACKEND_IMAGE_NAME):$(TAG)
FRONTEND_REMOTE_IMAGE := $(DOCKERHUB_USERNAME)/$(FRONTEND_IMAGE_NAME):$(TAG)
BACKEND_REMOTE_LATEST := $(DOCKERHUB_USERNAME)/$(BACKEND_IMAGE_NAME):latest
FRONTEND_REMOTE_LATEST := $(DOCKERHUB_USERNAME)/$(FRONTEND_IMAGE_NAME):latest
CONTAINER_NAME ?= pria-solo-backend

.PHONY: up down build logs logs-backend ps clean restart shell-backend shell-db migrate
.PHONY: compose-images compose-tag compose-tag-latest compose-push compose-push-latest image-sizes
.PHONY: backend-image backend-run backend-stop backend-logs backend-health backend-clean backend-tag backend-push backend-pull

# ----- Docker Compose (tugas terstruktur Modul 7) -----

up:
	docker compose up -d

build:
	docker compose up --build -d

down:
	docker compose down

logs:
	docker compose logs -f

logs-backend:
	docker compose logs -f backend

ps:
	docker compose ps

restart:
	docker compose restart

clean:
	docker compose down -v
	docker system prune -f

shell-backend:
	docker compose exec backend bash

shell-db:
	docker compose exec db mysql -uclouduser -pcloudpass cloudapp

migrate:
	docker compose exec frontend php artisan migrate --force

# ----- Docker Hub workflow (Modul 7: backend + frontend) -----

compose-images:
	docker compose build backend frontend

# Compose names images <project>-<service>:latest (e.g. pria-solo-backend:latest).
compose-tag:
	docker tag $(BACKEND_IMAGE_NAME):latest $(BACKEND_REMOTE_IMAGE)
	docker tag $(FRONTEND_IMAGE_NAME):latest $(FRONTEND_REMOTE_IMAGE)

compose-tag-latest: compose-tag
	docker tag $(BACKEND_IMAGE_NAME):latest $(BACKEND_REMOTE_LATEST)
	docker tag $(FRONTEND_IMAGE_NAME):latest $(FRONTEND_REMOTE_LATEST)

compose-push: compose-tag
	docker push $(BACKEND_REMOTE_IMAGE)
	docker push $(FRONTEND_REMOTE_IMAGE)

compose-push-latest: compose-tag-latest
	docker push $(BACKEND_REMOTE_IMAGE)
	docker push $(FRONTEND_REMOTE_IMAGE)
	docker push $(BACKEND_REMOTE_LATEST)
	docker push $(FRONTEND_REMOTE_LATEST)

image-sizes:
	docker images --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}" | grep -E "$(BACKEND_IMAGE_NAME)|$(FRONTEND_IMAGE_NAME)|REPOSITORY"

# ----- Backend image saja (legacy Modul 6 / Docker Hub) -----

backend-image:
	docker build -t $(BACKEND_LOCAL_IMAGE) ./backend

backend-run:
	docker run -d --name $(CONTAINER_NAME) -p 8001:8001 --env-file ./backend/.env $(BACKEND_LOCAL_IMAGE)

backend-stop:
	-docker stop $(CONTAINER_NAME)

backend-logs:
	docker logs -f $(CONTAINER_NAME)

backend-health:
	docker inspect --format='{{.State.Health.Status}}' $(CONTAINER_NAME)

backend-clean: backend-stop
	-docker rm $(CONTAINER_NAME)
	-docker rmi $(BACKEND_LOCAL_IMAGE)

backend-tag:
	docker tag $(BACKEND_LOCAL_IMAGE) $(BACKEND_REMOTE_IMAGE)

backend-push: backend-tag
	docker push $(BACKEND_REMOTE_IMAGE)

backend-pull:
	docker pull $(BACKEND_REMOTE_IMAGE)
