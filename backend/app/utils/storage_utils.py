import re
from pathlib import Path
from typing import Tuple


def get_latest_version(ticket_path: Path) -> int:

    if not ticket_path.exists():
        return 0

    version_folders = []
    for item in ticket_path.iterdir():
        if item.is_dir() and item.name.startswith("v"):
            match = re.match(r'v(\d+)', item.name)
            if match:
                version_folders.append(int(match.group(1)))

    return max(version_folders) if version_folders else 0


def create_version_storage(TEMP_STORAGE, ticket: str, version: int = None) -> Tuple[Path, int]:

    ticket_path = TEMP_STORAGE / ticket

    if version is None:
        # Auto-increment versi
        latest_version = get_latest_version(ticket_path)
        version = latest_version + 1

    version_storage = ticket_path / f"v{version}"

    try:
        # Buat folder versi dan subdirektori
        for subdir in ["images", "chunk"]:
            (version_storage / subdir).mkdir(parents=True, exist_ok=True)

        return version_storage, version

    except Exception as e:
        raise
