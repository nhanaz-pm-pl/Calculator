# General
**The plugin allows to execute math operations in the server or console with `/calculator` command**

![Eg Img](https://user-images.githubusercontent.com/60387689/194549412-e20377dd-43b5-4fe0-b386-52525757dc3e.png)

# Arithmetic Operators
- List of supported arithmetic operators: `/`, `*`, `+`, `-`, `**`, `%`.  
- Description of arithmetic operators: https://github.com/Muqsit/arithmexp/wiki/Operators
- Eg: `/calculator 1 + (2 - 3) * 4 / 5` > Result: `0.2`

# Constants
- List of supported constants: `e`, `log2e`, `log10e`, `ln2`, `ln10`, `pi`, `euler`.  
- Description of constants: https://github.com/Muqsit/arithmexp/wiki/List-of-pre-defined-constants
- Eg: `/calculator 3**2 * pi` > Result: `28.2`

# Math functions
- List of supported math functions: `abs`, `acos`, `acosh`, `asin`, `asinh`, `atan2`, `atan`, `atanh`, `ceil`, `cos`, `cosh`, `deg2rad`, `exp`, `expm1`, `fdiv`, `floor`, `fmod`, `getrandmax`, `hypot`, `intdiv`, `lcg_value`, `log10`, `log`, `max`, `min`, `mt_getrandmax`, `mt_rand`, `pi`, `pow`, `rad2deg`, `rand`, `round`, `sin`, `sinh`, `sqrt`, `srand`, `tan`, `tanh`.  
- Description of math functions: https://github.com/Muqsit/arithmexp/wiki/Function
- Eg: `/calculator sqrt(369)` > Result: `19.2`

# Commands & Permissions
```yaml
commands:
  calculator:
    description: "Perform a math operation"
    usage: "/calculator <expression>"
    permission: calculator.command
    aliases: ["calc"]
permissions:
  calculator.command:
    default: true
    description: "Allows the use of /calculator"
```

# Config
```yaml
---
# Prefix of messages.
prefix: "&f[&6Calculator&f]&r"

# {prefix} : Prefix.
# {result} : The result of the math.
result: "{prefix} &aResult: &b{result}"

# {prefix} : Prefix.
# {error} : Error information.
error: "{prefix} &cError: {error}"

# playSound: true > A sound will be sent to the player when a math operation is performed.
playSound: true
...

```

# Contact
[![Discord](https://img.shields.io/discord/986553214889517088?label=discord&color=7289DA&logo=discord)](https://discord.gg/j2X83ujT6c)\
**You can contact me directly via Discord `NhanAZ#9115`**
