<x-filament-panels::page>
    <form action="/submit" method="POST">
        <input type="text" name="captcha" placeholder="请输入验证码">
        <img src="{{ captcha_src('math') }}" alt="验证码" onclick="this.src='{{ captcha_src('math') }}?'+Math.random()">
        <button type="submit">提交</button>
    </form>
</x-filament-panels::page>
