from discord import Client, Message
from datetime import datetime
import asyncio

client = Client()

def is_command(content: str) -> bool:
    return content.startswith(';')

async def reply(message_in: Message, message_out: str):
    await client.send_message(message_in.channel, f'{message_in.author.mention} {message_out}')

@client.event
async def on_ready():
    print(f'Logged in as: {client.user.name} {client.user.id}')

@client.event
async def on_message(message):
    if not message.author.bot:
        await reply(message, message.content)
