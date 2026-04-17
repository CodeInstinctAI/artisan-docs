<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f1117; color: #e2e8f0; line-height: 1.6; }
        a { color: #60a5fa; text-decoration: none; }
        a:hover { text-decoration: underline; }

        .layout { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 260px; min-width: 260px; background: #1a1d27; border-right: 1px solid #2d3148; padding: 1.5rem 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; flex-shrink: 0; }
        .sidebar-header { padding: 0 1.25rem 1rem; border-bottom: 1px solid #2d3148; margin-bottom: 0.75rem; }
        .sidebar-header h1 { font-size: 1rem; font-weight: 700; color: #f8fafc; }
        .sidebar-header p { font-size: 0.75rem; color: #64748b; margin-top: 0.25rem; }
        .sidebar-group { margin-bottom: 0.25rem; }
        .sidebar-group-label { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #4b5563; padding: 0.4rem 1.25rem; }
        .sidebar-link { display: block; font-size: 0.78rem; padding: 0.28rem 1.25rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-link:hover { color: #e2e8f0; background: #252839; text-decoration: none; }

        /* Main content */
        .main { flex: 1; padding: 2.5rem 3rem; max-width: 960px; }
        .page-header { margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid #2d3148; }
        .page-header h1 { font-size: 2rem; font-weight: 800; color: #f8fafc; }
        .page-header .meta { margin-top: 0.5rem; font-size: 0.85rem; color: #64748b; }

        /* Group section */
        .group-section { margin-bottom: 3rem; }
        .group-title { font-size: 1.3rem; font-weight: 700; color: #c7d2fe; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid #2d3148; }

        /* Command card */
        .command-card { background: #1a1d27; border: 1px solid #2d3148; border-radius: 8px; margin-bottom: 1.25rem; overflow: hidden; }
        .command-header { display: flex; align-items: flex-start; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid #2d3148; }
        .command-name { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 1rem; font-weight: 700; color: #a5f3fc; }
        .command-desc { font-size: 0.85rem; color: #94a3b8; margin-top: 0.2rem; }
        .badge { font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 9999px; white-space: nowrap; }
        .badge-hidden { background: #3f3f46; color: #a1a1aa; }
        .badge-alias { background: #1e3a5f; color: #60a5fa; }

        .command-body { padding: 1rem 1.25rem; }
        .help-text { font-size: 0.85rem; color: #94a3b8; margin-bottom: 1rem; white-space: pre-wrap; }
        .aliases { font-size: 0.8rem; color: #64748b; margin-bottom: 0.75rem; }
        .aliases span { font-family: monospace; color: #94a3b8; }

        /* Tables */
        .section-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #4b5563; margin-bottom: 0.5rem; margin-top: 0.75rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-bottom: 0.5rem; }
        th { text-align: left; padding: 0.4rem 0.75rem; background: #111827; color: #6b7280; font-weight: 600; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 0.45rem 0.75rem; border-top: 1px solid #1e2333; color: #cbd5e1; vertical-align: top; }
        tr:hover td { background: #1e2333; }
        code { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.82em; background: #111827; padding: 0.1em 0.35em; border-radius: 4px; color: #a5f3fc; }
        .required-yes { color: #34d399; font-size: 0.75rem; }
        .required-no  { color: #6b7280; font-size: 0.75rem; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { padding: 1.5rem 1.25rem; }
        }
    </style>
</head>
<body>
<div class="layout">
    <nav class="sidebar">
        <div class="sidebar-header">
            <h1>{{ $title }}</h1>
            <p>{{ $total }} commands &bull; {{ count($groups) }} groups</p>
        </div>
        @foreach($groups as $group => $commands)
            <div class="sidebar-group">
                <div class="sidebar-group-label">{{ $group }}</div>
                @foreach($commands as $cmd)
                    <a class="sidebar-link" href="#{{ strtolower(preg_replace('/[^a-z0-9]+/i', '-', $cmd['name'])) }}" title="{{ $cmd['name'] }}">{{ $cmd['name'] }}</a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <main class="main">
        <div class="page-header">
            <h1>{{ $title }}</h1>
            <p class="meta">{{ $total }} commands across {{ count($groups) }} namespaces &mdash; Generated {{ $generatedAt }}</p>
        </div>

        @foreach($groups as $group => $commands)
            <section class="group-section" id="group-{{ strtolower(preg_replace('/[^a-z0-9]+/i', '-', $group)) }}">
                <h2 class="group-title">{{ $group }}</h2>

                @foreach($commands as $cmd)
                    <div class="command-card" id="{{ strtolower(preg_replace('/[^a-z0-9]+/i', '-', $cmd['name'])) }}">
                        <div class="command-header">
                            <div style="flex:1">
                                <div class="command-name">{{ $cmd['name'] }}</div>
                                @if($cmd['description'])
                                    <div class="command-desc">{{ $cmd['description'] }}</div>
                                @endif
                            </div>
                            @if($cmd['hidden'])
                                <span class="badge badge-hidden">hidden</span>
                            @endif
                        </div>

                        <div class="command-body">
                            @if($cmd['help'])
                                <p class="help-text">{{ $cmd['help'] }}</p>
                            @endif

                            @if(!empty($cmd['aliases']))
                                <p class="aliases">Aliases:
                                    @foreach($cmd['aliases'] as $alias)
                                        <span>{{ $alias }}</span>{{ !$loop->last ? ',' : '' }}
                                    @endforeach
                                </p>
                            @endif

                            @if(!empty($cmd['arguments']))
                                <div class="section-label">Arguments</div>
                                <table>
                                    <thead><tr><th>Name</th><th>Required</th><th>Description</th><th>Default</th></tr></thead>
                                    <tbody>
                                    @foreach($cmd['arguments'] as $arg)
                                        <tr>
                                            <td><code>{{ $arg['name'] }}</code></td>
                                            <td>
                                                @if($arg['required'])
                                                    <span class="required-yes">&#10003; Yes</span>
                                                @else
                                                    <span class="required-no">No</span>
                                                @endif
                                            </td>
                                            <td>{{ $arg['description'] ?: '—' }}</td>
                                            <td>{{ $arg['default'] !== null ? json_encode($arg['default']) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @endif

                            @if(!empty($cmd['options']))
                                <div class="section-label">Options</div>
                                <table>
                                    <thead><tr><th>Option</th><th>Shortcut</th><th>Required</th><th>Description</th><th>Default</th></tr></thead>
                                    <tbody>
                                    @foreach($cmd['options'] as $opt)
                                        <tr>
                                            <td><code>{{ $opt['name'] }}</code></td>
                                            <td>{{ $opt['shortcut'] ?? '—' }}</td>
                                            <td>
                                                @if($opt['required'])
                                                    <span class="required-yes">&#10003; Yes</span>
                                                @else
                                                    <span class="required-no">No</span>
                                                @endif
                                            </td>
                                            <td>{{ $opt['description'] ?: '—' }}</td>
                                            <td>{{ $opt['default'] !== null && $opt['default'] !== false ? json_encode($opt['default']) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                @endforeach
            </section>
        @endforeach
    </main>
</div>
</body>
</html>
