import { expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

export async function audit(page) {
    await page.evaluate(async () => {
        await document.fonts.ready;
        await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
        // Closed native details can retain pending animations indefinitely.
        // Wait only for finite animations outside closed native panels.
        await Promise.all(document.getAnimations().filter(animation => !animation.effect?.target?.closest?.('details:not([open]), [hidden]')
            && animation.effect?.getComputedTiming().iterations !== Infinity)
            .map(animation => animation.finished.catch(() => {})));
    });
    const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
    expect(result.violations.map(({ id, nodes }) => ({ id, targets: nodes.map(node => ({ target: node.target, detail: node.failureSummary })) }))).toEqual([]);
    const overflow = await page.evaluate(() => ({ width: innerWidth, scroll: document.documentElement.scrollWidth,
        elements: [...document.querySelectorAll('body *')].filter(element => element.getBoundingClientRect().right > innerWidth + 1)
            .slice(0, 15).map(element => `${element.tagName}.${element.className}`) }));
    expect(overflow.scroll, JSON.stringify(overflow)).toBeLessThanOrEqual(overflow.width + 1);
}
