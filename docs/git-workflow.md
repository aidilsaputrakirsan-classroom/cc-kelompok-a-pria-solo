# Git Workflow Guide - Tim PRIA SOLO

## Tujuan

Dokumen ini menjadi panduan standar kolaborasi Git agar branch `main` tetap stabil, perubahan dapat direview, dan histori commit tetap rapi.

---

## 1. Branching Strategy

Kita menggunakan **GitHub Flow**:

1. Buat branch baru dari `main` terbaru.
2. Kerjakan perubahan di branch tersebut.
3. Push branch dan buat Pull Request (PR).
4. Lakukan review.
5. Merge ke `main` menggunakan **Squash and Merge**.
6. Hapus branch setelah merge.

### Naming Convention Branch

Format: `tipe/deskripsi-singkat` (lowercase, kebab-case)

- `feature/...` untuk fitur baru  
  contoh: `feature/makefile-update`
- `fix/...` untuk perbaikan bug  
  contoh: `fix/health-endpoint-timeout`
- `docs/...` untuk dokumentasi  
  contoh: `docs/git-workflow-guide`
- `refactor/...` untuk refactor tanpa mengubah behavior
- `chore/...` untuk maintenance, config, dependencies

---

## 2. Commit Message Convention

Menggunakan **Conventional Commits**:

- `feat: ...`
- `fix: ...`
- `docs: ...`
- `refactor: ...`
- `chore: ...`
- `test: ...`
- `style: ...`

Contoh:

- `feat: add PR quality targets in Makefile`
- `docs: add team git workflow guide`

---

## 3. Pull Request Process

### Wajib sebelum membuat PR

- Sudah menarik perubahan terbaru dan membuat branch dari basis yang tepat.
- Scope perubahan jelas dan terfokus.
- Tidak ada hardcoded secrets atau credentials.
- Sudah menjalankan pengecekan lokal (gunakan `make pr-check` jika tersedia).

### Isi PR minimum

- Title mengikuti Conventional Commits.
- Deskripsi perubahan (apa dan kenapa).
- Assign reviewer minimal 1 orang.
- Sertakan screenshot jika perubahan UI.

### Merge policy

- Gunakan **Squash and Merge**.
- Pastikan minimal 1 approval.
- Hapus branch setelah merge.

---

## 4. Code Review Guidelines

Reviewer minimal memberi 1 komentar substantif (lebih baik 2-3), mencakup:

1. **Fungsionalitas**: apakah sesuai requirement.
2. **Readability**: apakah kode mudah dipahami.
3. **Best practice**: apakah sesuai pola proyek.
4. **Edge cases**: apakah kasus gagal sudah dipikirkan.
5. **Security**: ada risiko credential atau data exposure.

Contoh komentar yang baik:

- "Saran: tambahkan validasi input kosong untuk mencegah request invalid."
- "Kenapa memilih status code 200 di sini? Bisa jelaskan pertimbangannya?"

---

## 5. CODEOWNERS

File `.github/CODEOWNERS` dipakai untuk auto-reviewer berdasarkan area perubahan.

Contoh pemetaan:

- `/backend/` -> Lead Backend
- `/frontend/` -> Lead Frontend
- `docker-compose.yml`, `Makefile` -> Lead DevOps
- `/docs/`, `README.md` -> Lead QA & Docs

Catatan:

- Pastikan username GitHub valid (format `@username`).
- Update CODEOWNERS saat pembagian peran tim berubah.

---

## 6. Larangan dan Anti-Pattern

- Direct push ke `main`.
- PR tanpa deskripsi.
- Review "LGTM" tanpa membaca perubahan.
- Merge PR sendiri tanpa review (kecuali kondisi darurat yang disepakati).

---

## 7. Checklist Cepat (Developer)

- [ ] Branch sesuai konvensi.
- [ ] Commit message sesuai Conventional Commits.
- [ ] PR berisi deskripsi yang jelas.
- [ ] Minimal 1 reviewer ditugaskan.
- [ ] Merge via Squash and Merge.
- [ ] Branch dihapus setelah merge.
