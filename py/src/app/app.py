from discord import Client, Message
from datetime import datetime
import asyncio

from .character import Character

client = Client()

def is_command(message: Message) -> bool:
    return message.content.startswith(';')

def get_command(message: Message) -> str:
    return message.content[1:]

async def reply(message_in: Message, message_out: str):
    await client.send_message(message_in.channel, f'{message_in.author.mention} {message_out}')

@client.event
async def on_ready():
    print(f'Logged in as: {client.user.name} {client.user.id}')

@client.event
async def on_message(message):
    if is_command(message):
        if get_command(message) == 'gen_char':
            await reply(message, str(Character(message.author.nick or message.author.name)))
