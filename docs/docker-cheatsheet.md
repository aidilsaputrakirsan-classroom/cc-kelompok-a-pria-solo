# Docker Cheatsheet (pria-solo)

Cheatsheet ini disesuaikan untuk proyek intern:
- `backend/` = FastAPI (`app.main:app`) berjalan di port `8001`
- `frontend/` = Laravel + Open Admin (non-Docker pada Modul 5)

---

## 1) Build, Run, Logs (Backend FastAPI)

```bash
# Build image backend
docker build -t pria-solo-backend:v1 ./backend

# Run container (detached)
docker run -d --name pria-solo-backend \
  -p 8001:8001 \
  --env-file ./backend/.env \
  pria-solo-backend:v1

# Check running containers
docker ps

# Stream logs
docker logs -f pria-solo-backend
```

## 2) Healthcheck & Inspection

```bash
# Health status from Docker HEALTHCHECK
docker inspect --format='{{.State.Health.Status}}' pria-solo-backend

# Inspect complete metadata
docker inspect pria-solo-backend

# Show image layers/history
docker history pria-solo-backend:v1
```

## 3) Debugging Inside Container

```bash
# Open shell
docker exec -it pria-solo-backend bash

# Check process and env manually
python --version
env
exit
```

## 4) Stop, Remove, Cleanup

```bash
# Stop and remove container
docker stop pria-solo-backend
docker rm pria-solo-backend

# Remove image
docker rmi pria-solo-backend:v1

# Remove dangling objects
docker image prune
```

## 5) Docker Hub Push/Pull

```bash
# Login once
docker login

# Tag local image to Docker Hub repository
docker tag pria-solo-backend:v1 USERNAME/pria-solo-backend:v1

# Push
docker push USERNAME/pria-solo-backend:v1

# Pull on another machine
docker pull USERNAME/pria-solo-backend:v1
docker run -p 8001:8001 --env-file ./backend/.env USERNAME/pria-solo-backend:v1
```

## 6) Makefile Shortcuts (Repo Root)

**Docker Compose (stack penuh — Modul 7):**

```bash
make up
make build          # compose up --build -d
make down
make logs
make ps
make migrate
make clean          # down -v + prune (hapus data DB)
```

**Image backend saja / Docker Hub:**

```bash
make backend-image
make backend-run
make backend-logs
make backend-health
make backend-push DOCKERHUB_USERNAME=yourusername
make backend-clean
```

**Compose images (backend + frontend) ke Docker Hub:**

```bash
make compose-images
make compose-push-latest DOCKERHUB_USERNAME=yourusername TAG=v1
make image-sizes
```

## 7) Catatan Integrasi Frontend Laravel

- Laravel di `frontend/` umumnya berjalan di `http://127.0.0.1:8000`.
- FastAPI container berjalan di `http://127.0.0.1:8001`.
- Pastikan variabel frontend yang memanggil API backend mengarah ke port `8001`.
