import requests
import aria2p
from datetime import datetime
from status import format_progress_bar
import asyncio
import os, time
import logging
from pyrogram.types import InlineKeyboardMarkup, InlineKeyboardButton

# List of Terabox-like domains to normalize
TERABOX_DOMAINS = [
    'terabox.com', 'nephobox.com', '4funbox.com', 'mirrobox.com',
    'momerybox.com', 'teraboxapp.com', '1024tera.com',
    'terabox.app', 'gibibox.com', 'goaibox.com', 'terasharelink.com',
    'teraboxlink.com', 'terafileshare.com'
]

# Normalizes URLs to standard terabox.com/s/ format
def normalize_terabox_url(url):
    for domain in TERABOX_DOMAINS:
        if domain in url:
            parts = url.split('/s/')
            if len(parts) > 1:
                return f"https://terabox.com/s/{parts[1]}"
    return url

aria2 = aria2p.API(
    aria2p.Client(
        host="http://localhost",
        port=6800,
        secret=""
    )
)

options = {
    "max-tries": "50",
    "retry-wait": "3",
    "continue": "true"
}
aria2.set_global_options(options)

# Download video function
async def download_video(url, reply_msg, user_mention, user_id):
    url = normalize_terabox_url(url)

    try:
        response = requests.get(f"http://178.62.122.48:6999/?url={url}")
        response.raise_for_status()
        data = response.json()

        direct_link = data.get("link")
        file_name = data.get("file_name")
        thumbnail_url = data.get("thumb")

        if not direct_link:
            logging.error("Direct download link not found in API response")
            return None, None, None

        # Add the download task to aria2
        download = aria2.add_uris([direct_link])
        start_time = datetime.now()

        # While downloading, update the progress
        while not download.is_complete:
            download.update()
            percentage = download.progress
            done = download.completed_length
            total_size = download.total_length
            speed = download.download_speed
            eta = download.eta
            elapsed_time_seconds = (datetime.now() - start_time).total_seconds()

            progress_text = format_progress_bar(
                filename=file_name,
                percentage=percentage,
                done=done,
                total_size=total_size,
                status="Downloading",
                eta=eta,
                speed=speed,
                elapsed=elapsed_time_seconds,
                user_mention=user_mention,
                user_id=user_id,
                aria2p_gid=download.gid
            )
            await reply_msg.edit_text(progress_text)
            await asyncio.sleep(2)

        if download.is_complete:
            file_path = download.files[0].path
            thumbnail_path = None

            if thumbnail_url:
                try:
                    thumbnail_response = requests.get(thumbnail_url)
                    thumbnail_path = "thumbnail.jpg"
                    with open(thumbnail_path, "wb") as thumb_file:
                        thumb_file.write(thumbnail_response.content)
                except Exception as e:
                    logging.warning(f"Failed to fetch thumbnail: {e}")
                    thumbnail_path = None

            await reply_msg.edit_text("Uploading...")

            return file_path, thumbnail_path, file_name

    except Exception as e:
        logging.error(f"Error during download: {e}")
        buttons = [[InlineKeyboardButton("📥 Direct Download", url=direct_link)]]
        reply_markup = InlineKeyboardMarkup(buttons)
        await reply_msg.reply_text(
            "Failed to download automatically. Please use the link below to download manually.",
            reply_markup=reply_markup
        )
        return None, None, None


# Upload video function
async def upload_video(client, file_path, thumbnail_path, video_title, reply_msg, collection_channel_id, user_mention, user_id, message):
    try:
        file_size = os.path.getsize(file_path)
        uploaded = 0
        start_time = datetime.now()
        last_update_time = time.time()

        # Progress function
        async def progress(current, total):
            nonlocal uploaded, last_update_time
            uploaded = current
            if total > 0:
                percentage = (current / total) * 100
                elapsed_time_seconds = (datetime.now() - start_time).total_seconds()
                speed = current / elapsed_time_seconds if elapsed_time_seconds > 0 else 0
                eta = (total - current) / speed if speed > 0 else 0
            else:
                percentage, speed, eta = 0, 0, 0

            # Update progress every 2 seconds
            if time.time() - last_update_time > 2:
                progress_text = format_progress_bar(
                    filename=video_title,
                    percentage=percentage,
                    done=current,
                    total_size=total,
                    status="Uploading",
                    eta=eta,
                    speed=speed,
                    elapsed=elapsed_time_seconds,
                    user_mention=user_mention,
                    user_id=user_id,
                    aria2p_gid=""
                )
                try:
                    await reply_msg.edit_text(progress_text)
                    last_update_time = time.time()
                except Exception as e:
                    logging.warning(f"Progress message update failed: {e}")

        # Upload the video to the collection channel
        with open(file_path, 'rb') as file:
            collection_message = await client.send_video(
                chat_id=collection_channel_id,
                video=file,
                caption=f"✨ {video_title}\n👤 Leech by: {user_mention}\n📥 User link: @PythonBotz",
                thumb=thumbnail_path,
                progress=progress
            )

        # Copy the message to the original chat
        await client.copy_message(
            chat_id=message.chat.id,
            from_chat_id=collection_channel_id,
            message_id=collection_message.id
        )

        # Delete the reply message after upload
        await reply_msg.delete()
        sticker_message = await message.reply_sticker("CAACAgUAAxkBAAEBOXRoBYCH9ZVYpx_suIxK7wagcOChTwAC0BcAApNCMFTOyuCdOZrAdjYE")

        # Clean up local files (video and thumbnail)
        for path in [file_path, thumbnail_path]:
            if path and os.path.exists(path):
                os.remove(path)

        # Wait for some time before deleting the sticker message
        await asyncio.sleep(5)
        await sticker_message.delete()

        return collection_message.id

    except Exception as e:
        logging.error(f"Upload failed: {e}")
        await reply_msg.edit_text("❌ Upload failed. Please try again later.\nJoin > @PythonBotz")
        return None
        
