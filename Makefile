DOCKERHUB_USERNAME ?= YOUR_DOCKERHUB_USERNAME
IMAGE_NAME ?= pria-solo-backend
TAG ?= v1
BACKEND_IMAGE := $(IMAGE_NAME):$(TAG)
REMOTE_IMAGE := $(DOCKERHUB_USERNAME)/$(IMAGE_NAME):$(TAG)
CONTAINER_NAME ?= pria-solo-backend

.PHONY: build run stop logs clean tag push pull health

build:
	docker build -t $(BACKEND_IMAGE) ./backend

run:
	docker run -d --name $(CONTAINER_NAME) -p 8001:8001 --env-file ./backend/.env $(BACKEND_IMAGE)

stop:
	-docker stop $(CONTAINER_NAME)

logs:
	docker logs -f $(CONTAINER_NAME)

health:
	docker inspect --format='{{.State.Health.Status}}' $(CONTAINER_NAME)

clean: stop
	-docker rm $(CONTAINER_NAME)
	-docker rmi $(BACKEND_IMAGE)

tag:
	docker tag $(BACKEND_IMAGE) $(REMOTE_IMAGE)

push: tag
	docker push $(REMOTE_IMAGE)

pull:
	docker pull $(REMOTE_IMAGE)
